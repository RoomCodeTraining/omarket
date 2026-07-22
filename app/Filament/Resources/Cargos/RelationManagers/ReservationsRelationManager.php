<?php

namespace App\Filament\Resources\Cargos\RelationManagers;

use App\Enums\ReservationStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Reservation;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reservations';

    protected static ?string $title = 'Réservations';

    protected static ?string $modelLabel = 'réservation';

    protected static ?string $pluralModelLabel = 'réservations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf. résa')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('cargoItem.product.name')
                    ->label('Produit')
                    ->searchable(),
                TextColumn::make('guest_name')
                    ->label('Client')
                    ->state(fn (Reservation $record): string => $record->guest_name
                        ?: $record->user?->name
                        ?: '—'),
                TextColumn::make('quantity')
                    ->label('Qté')
                    ->alignCenter(),
                TextColumn::make('unit_price_cents')
                    ->label('Prix unit.')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ReservationStatus $state): string => $state->label()),
                TextColumn::make('order.reference')
                    ->label('Commande')
                    ->placeholder('—')
                    ->url(fn (Reservation $record): ?string => $record->order_id
                        ? OrderResource::getUrl('edit', ['record' => $record->order_id])
                        : null),
                TextColumn::make('created_at')
                    ->label('Créée')
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(ReservationStatus::cases())->mapWithKeys(
                        fn (ReservationStatus $status) => [$status->value => $status->label()],
                    )),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('open_order')
                    ->label('Voir commande')
                    ->visible(fn (Reservation $record): bool => filled($record->order_id))
                    ->url(fn (Reservation $record): string => OrderResource::getUrl('edit', ['record' => $record->order_id])),
            ])
            ->emptyStateHeading('Aucune réservation')
            ->emptyStateDescription('Les réservations boutique apparaîtront ici.');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
