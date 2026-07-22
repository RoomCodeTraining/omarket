<?php

namespace App\Filament\Resources\Cargos\Schemas;

use App\Enums\CargoStatus;
use Filament\Forms\Components\DatePicker;
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
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(40)
                        ->placeholder('CG-ABJ-MTL-0726')
                        ->helperText('Identifiant unique du cargo.'),
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
        ]);
    }
}
