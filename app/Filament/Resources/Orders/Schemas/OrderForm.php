<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Référence')
                ->description('Identifiant de suivi — généré automatiquement à la création.')
                ->icon('heroicon-o-hashtag')
                ->columns(2)
                ->schema([
                    TextInput::make('reference')
                        ->label('Référence')
                        ->disabled()
                        ->dehydrated(),
                    Select::make('type')
                        ->label('Type')
                        ->options(collect(OrderType::cases())->mapWithKeys(
                            fn (OrderType $type) => [$type->value => $type->label()],
                        ))
                        ->required()
                        ->native(false)
                        ->live(),
                    Select::make('cargo_id')
                        ->label('Arrivage')
                        ->relationship('cargo', 'code')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->visible(fn (Get $get): bool => $get('type') === OrderType::Cargo->value)
                        ->required(fn (Get $get): bool => $get('type') === OrderType::Cargo->value)
                        ->helperText('Commande rattachée à un cargo / arrivage.')
                        ->columnSpanFull(),
                ]),
            Section::make('Client')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    TextInput::make('guest_name')
                        ->label('Nom')
                        ->maxLength(120),
                    TextInput::make('guest_email')
                        ->label('E-mail')
                        ->email()
                        ->maxLength(160),
                    Select::make('user_id')
                        ->label('Compte client lié')
                        ->relationship('user', 'email')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->columnSpanFull(),
                ]),
            Section::make('Montants')
                ->description('Le sous-total est recalculé depuis les lignes. Les frais de port sont éditables.')
                ->icon('heroicon-o-banknotes')
                ->columns(3)
                ->schema([
                    TextInput::make('subtotal_cents')
                        ->label('Sous-total (cents)')
                        ->disabled()
                        ->dehydrated()
                        ->prefix('¢'),
                    TextInput::make('shipping_cents')
                        ->label('Frais de port (cents)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('¢')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            $set('total_cents', (int) $get('subtotal_cents') + (int) $state);
                        }),
                    TextInput::make('total_cents')
                        ->label('Total (cents)')
                        ->disabled()
                        ->dehydrated()
                        ->prefix('¢'),
                ]),
            Section::make('Notes')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes internes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
