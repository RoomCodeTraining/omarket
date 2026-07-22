<?php

namespace App\Filament\Resources\Cargos\Tables;

use App\Enums\CargoStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CargosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('route')
                    ->label('Trajet')
                    ->state(fn ($record): string => $record->routeLabel()),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (CargoStatus $state): string => match ($state) {
                        CargoStatus::Draft => 'gray',
                        CargoStatus::Open => 'success',
                        CargoStatus::Closed => 'warning',
                        CargoStatus::InTransit => 'info',
                        CargoStatus::Arrived => 'primary',
                        CargoStatus::Completed => 'gray',
                    })
                    ->formatStateUsing(fn (CargoStatus $state): string => $state->label()),
                TextColumn::make('departure_at')
                    ->label('Départ')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('estimated_arrival_at')
                    ->label('ETA')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Lignes')
                    ->counts('items')
                    ->alignCenter(),
                TextColumn::make('orders_count')
                    ->label('Commandes')
                    ->counts('orders')
                    ->alignCenter(),
                TextColumn::make('updated_at')
                    ->label('Maj')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('estimated_arrival_at')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(CargoStatus::cases())->mapWithKeys(
                        fn (CargoStatus $status) => [$status->value => $status->label()],
                    )),
            ])
            ->recordActions([
                ViewAction::make()->label('Voir'),
                EditAction::make()->label('Éditer'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }
}
