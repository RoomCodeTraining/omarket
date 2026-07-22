<?php

namespace App\Filament\Resources\CustomRequests\Tables;

use App\Enums\CustomRequestStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Demande')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->weight('medium'),
                TextColumn::make('client')
                    ->label('Client')
                    ->state(fn ($record): string => $record->guest_name
                        ?: $record->user?->name
                        ?: '—')
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($builder) use ($search): void {
                            $builder->where('guest_name', 'like', "%{$search}%")
                                ->orWhere('guest_email', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($userQuery) => $userQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%"));
                        });
                    }),
                TextColumn::make('guest_email')
                    ->label('E-mail')
                    ->state(fn ($record): string => $record->guest_email
                        ?: $record->user?->email
                        ?: '—')
                    ->toggleable(),
                TextColumn::make('quantity')
                    ->label('Qté')
                    ->alignCenter(),
                TextColumn::make('budget_cents')
                    ->label('Budget')
                    ->formatStateUsing(fn (?int $state): string => $state === null
                        ? '—'
                        : number_format($state / 100, 2, ',', ' ').' $'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (CustomRequestStatus $state): string => match ($state) {
                        CustomRequestStatus::Submitted => 'warning',
                        CustomRequestStatus::InReview => 'info',
                        CustomRequestStatus::Quoted => 'primary',
                        CustomRequestStatus::Accepted => 'success',
                        CustomRequestStatus::Rejected, CustomRequestStatus::Cancelled => 'danger',
                    })
                    ->formatStateUsing(fn (CustomRequestStatus $state): string => $state->label()),
                TextColumn::make('quotes_count')
                    ->label('Devis')
                    ->counts('quotes')
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Reçue')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(CustomRequestStatus::cases())->mapWithKeys(
                        fn (CustomRequestStatus $status) => [$status->value => $status->label()],
                    )),
            ])
            ->recordActions([
                ViewAction::make()->label('Voir'),
                EditAction::make()->label('Traiter'),
            ]);
    }
}
