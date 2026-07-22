<?php

namespace App\Filament\Resources\Cargos\RelationManagers;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Commandes de l’arrivage';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->modifyQueryUsing(fn ($query) => $query->where('type', OrderType::Cargo))
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->copyable()
                    ->weight('medium'),
                TextColumn::make('customer')
                    ->label('Client')
                    ->state(fn (Order $record): string => $record->customerName()),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color())
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                TextColumn::make('items_count')
                    ->label('Lignes')
                    ->counts('items')
                    ->alignCenter(),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $'),
                TextColumn::make('placed_at')
                    ->label('Passée')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('placed_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $status) => [$status->value => $status->label()],
                    )),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('open')
                    ->label('Ouvrir')
                    ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Aucune commande sur cet arrivage')
            ->emptyStateDescription('Les réservations clients créent automatiquement une commande liée à ce cargo.');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
