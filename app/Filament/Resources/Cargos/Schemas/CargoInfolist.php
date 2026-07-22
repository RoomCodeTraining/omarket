<?php

namespace App\Filament\Resources\Cargos\Schemas;

use App\Enums\CargoStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CargoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Arrivage')
                ->icon('heroicon-o-truck')
                ->columns(2)
                ->schema([
                    TextEntry::make('code')->label('Code'),
                    TextEntry::make('name')->label('Nom'),
                    TextEntry::make('origin')->label('Origine'),
                    TextEntry::make('destination')->label('Destination'),
                    TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn (CargoStatus $state): string => $state->label()),
                    TextEntry::make('departure_at')
                        ->label('Départ')
                        ->date('d/m/Y')
                        ->placeholder('—'),
                    TextEntry::make('estimated_arrival_at')
                        ->label('ETA Canada')
                        ->date('d/m/Y')
                        ->placeholder('—'),
                    TextEntry::make('items_count')
                        ->label('Lignes produits')
                        ->state(fn ($record): int => $record->items()->count()),
                    TextEntry::make('notes')
                        ->label('Notes')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
