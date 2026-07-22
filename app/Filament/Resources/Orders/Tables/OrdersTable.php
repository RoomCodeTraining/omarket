<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Filament\Resources\Cargos\CargoResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),
                TextColumn::make('customer')
                    ->label('Client')
                    ->state(fn (Order $record): string => $record->customerName())
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($builder) use ($search): void {
                            $builder->where('guest_name', 'like', "%{$search}%")
                                ->orWhere('guest_email', 'like', "%{$search}%")
                                ->orWhere('reference', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($userQuery) => $userQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%"));
                        });
                    }),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (OrderType $state): string => $state->label()),
                TextColumn::make('cargo.code')
                    ->label('Arrivage')
                    ->placeholder('—')
                    ->toggleable()
                    ->url(fn (Order $record): ?string => $record->cargo_id
                        ? CargoResource::getUrl('edit', ['record' => $record->cargo_id])
                        : null),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color())
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Lignes')
                    ->counts('items')
                    ->alignCenter(),
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
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(collect(OrderType::cases())->mapWithKeys(
                        fn (OrderType $type) => [$type->value => $type->label()],
                    )),
            ])
            ->recordActions([
                ViewAction::make()->label('Voir'),
                EditAction::make()->label('Gérer'),
                Action::make('advance')
                    ->label('Statut suivant')
                    ->icon('heroicon-o-arrow-right')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status->allowedTransitions() !== [])
                    ->requiresConfirmation()
                    ->modalHeading(fn (Order $record): string => 'Passer à « '.($record->status->allowedTransitions()[0]->label() ?? '').' » ?')
                    ->action(function (Order $record, UpdateOrderStatus $updateOrderStatus): void {
                        $next = $record->status->allowedTransitions()[0] ?? null;

                        if (! $next) {
                            return;
                        }

                        // Prefer non-cancelled transition when multiple options exist
                        $preferred = collect($record->status->allowedTransitions())
                            ->first(fn (OrderStatus $status): bool => $status !== OrderStatus::Cancelled)
                            ?? $next;

                        $updateOrderStatus->handle($record, $preferred);

                        Notification::make()
                            ->title('Statut mis à jour : '.$preferred->label())
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
