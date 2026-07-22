<?php

namespace App\Filament\Resources\CustomRequests\Schemas;

use App\Enums\CustomRequestStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Client')
                ->description('Coordonnées fournies avec la demande.')
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
                        ->helperText('Optionnel — compte Laravel associé.'),
                ]),
            Section::make('Demande')
                ->description('Produit recherché et contraintes client.')
                ->icon('heroicon-o-shopping-bag')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Titre')
                        ->required()
                        ->maxLength(160)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                    TextInput::make('quantity')
                        ->label('Quantité')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(1),
                    TextInput::make('budget_cents')
                        ->label('Budget indicatif (cents CAD)')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('¢')
                        ->helperText('Ex. 8000 = 80,00 $ — laissez vide si non précisé.'),
                ]),
            Section::make('Suivi')
                ->description('Statut traité par l’équipe Ôhéfê.')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Select::make('status')
                        ->label('Statut')
                        ->options(collect(CustomRequestStatus::cases())->mapWithKeys(
                            fn (CustomRequestStatus $status) => [$status->value => $status->label()],
                        ))
                        ->required()
                        ->native(false)
                        ->helperText('Passez en « Devis envoyé » automatiquement via l’action Envoyer sur un devis.'),
                ]),
        ]);
    }
}
