<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Commande')
                ->icon('heroicon-o-clipboard-document-list')
                ->columns(2)
                ->schema([
                    TextEntry::make('reference')->label('Référence')->copyable(),
                    TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->color(fn (OrderStatus $state): string => $state->color())
                        ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                    TextEntry::make('type')
                        ->label('Type')
                        ->formatStateUsing(fn (OrderType $state): string => $state->label()),
                    TextEntry::make('cargo.code')
                        ->label('Arrivage')
                        ->placeholder('—')
                        ->visible(fn ($record): bool => $record->type === OrderType::Cargo),
                    TextEntry::make('placed_at')
                        ->label('Passée le')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),
                    TextEntry::make('customer')
                        ->label('Client')
                        ->state(fn ($record): string => $record->customerName()),
                    TextEntry::make('email')
                        ->label('E-mail')
                        ->state(fn ($record): ?string => $record->customerEmail())
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('subtotal_cents')
                        ->label('Sous-total')
                        ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $'),
                    TextEntry::make('shipping_cents')
                        ->label('Livraison')
                        ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $'),
                    TextEntry::make('total_cents')
                        ->label('Total')
                        ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $')
                        ->weight('bold'),
                    TextEntry::make('notes')
                        ->label('Notes')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
