<?php

namespace App\Filament\Resources\CustomRequests\Schemas;

use App\Enums\CustomRequestStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Course')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('title')->label('Titre résumé')->columnSpanFull(),
                    TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn (CustomRequestStatus $state): string => $state->label()),
                    TextEntry::make('quantity')->label('Quantité totale'),
                    TextEntry::make('budget_cents')
                        ->label('Budget total')
                        ->formatStateUsing(fn (?int $state): string => $state === null
                            ? '—'
                            : number_format($state / 100, 2, ',', ' ').' $'),
                    TextEntry::make('guest_name')
                        ->label('Client')
                        ->state(fn ($record): string => $record->guest_name
                            ?: $record->user?->name
                            ?: '—'),
                    TextEntry::make('guest_email')
                        ->label('E-mail')
                        ->state(fn ($record): string => $record->guest_email
                            ?: $record->user?->email
                            ?: '—')
                        ->copyable(),
                    TextEntry::make('description')
                        ->label('Notes')
                        ->columnSpanFull(),
                    TextEntry::make('created_at')
                        ->label('Reçue le')
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('validated_at')
                        ->label('Validée le')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('Pas encore validée'),
                ]),
            Section::make('Produits')
                ->icon('heroicon-o-shopping-bag')
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            TextEntry::make('label')->label('Libellé'),
                            TextEntry::make('quantity')->label('Quantité'),
                            TextEntry::make('budget_cents')
                                ->label('Budget')
                                ->formatStateUsing(fn (?int $state): string => $state === null
                                    ? '—'
                                    : number_format($state / 100, 2, ',', ' ').' $'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
            Section::make('Fournisseur')
                ->icon('heroicon-o-building-storefront')
                ->columns(2)
                ->schema([
                    IconEntry::make('has_supplier')
                        ->label('A un fournisseur')
                        ->boolean(),
                    TextEntry::make('supplier_name')
                        ->label('Nom')
                        ->placeholder('—'),
                    TextEntry::make('supplier_contact')
                        ->label('Contact')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
            Section::make('Cargo')
                ->icon('heroicon-o-truck')
                ->columns(2)
                ->schema([
                    TextEntry::make('cargo.code')
                        ->label('Code')
                        ->placeholder('Non associé'),
                    TextEntry::make('cargo.name')
                        ->label('Nom')
                        ->placeholder('—'),
                    TextEntry::make('cargo.estimated_arrival_at')
                        ->label('ETA Canada')
                        ->date('d/m/Y')
                        ->placeholder('—'),
                    TextEntry::make('cargo_assigned_at')
                        ->label('Associé le')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),
                ]),
        ]);
    }
}
