<?php

namespace App\Filament\Resources\Cargos\Schemas;

use App\Enums\CargoStatus;
use App\Filament\Resources\Cargos\Support\CargoItemsRepeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CargoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité de l’arrivage')
                ->description('Code cargo et trajet CI → Canada.')
                ->icon('heroicon-o-truck')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Généré automatiquement à la création')
                        ->helperText('Le numéro de cargo est attribué automatiquement par le système.')
                        ->visibleOn('edit'),
                    Placeholder::make('code_preview')
                        ->label('Code')
                        ->content('Généré automatiquement à l’enregistrement.')
                        ->visibleOn('create'),
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(160)
                        ->placeholder('Arrivage juillet Abidjan → Montréal'),
                    TextInput::make('origin')
                        ->label('Origine')
                        ->required()
                        ->default('Abidjan')
                        ->maxLength(80),
                    TextInput::make('destination')
                        ->label('Destination')
                        ->required()
                        ->default('Montréal')
                        ->maxLength(80),
                ]),
            Section::make('Planning & statut')
                ->description('Visible côté boutique quand le statut est « Ouvert aux réservations ».')
                ->icon('heroicon-o-calendar-days')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Statut')
                        ->options(collect(CargoStatus::cases())->mapWithKeys(
                            fn (CargoStatus $status) => [$status->value => $status->label()],
                        ))
                        ->required()
                        ->default(CargoStatus::Draft->value)
                        ->native(false),
                    DatePicker::make('departure_at')
                        ->label('Départ')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    DatePicker::make('estimated_arrival_at')
                        ->label('ETA Canada')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    Textarea::make('notes')
                        ->label('Notes internes / client')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
            Section::make('Produits de l’arrivage')
                ->description('Ajoutez plusieurs lignes d’un coup : produit, quantité, prix.')
                ->icon('heroicon-o-cube')
                ->visibleOn('create')
                ->schema([
                    CargoItemsRepeater::make()
                        ->minItems(0)
                        ->defaultItems(0)
                        ->helperText('Optionnel à la création — vous pourrez aussi ajouter des lignes ensuite.'),
                ]),
        ]);
    }
}
