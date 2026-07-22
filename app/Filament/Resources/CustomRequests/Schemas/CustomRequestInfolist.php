<?php

namespace App\Filament\Resources\CustomRequests\Schemas;

use App\Enums\CustomRequestStatus;
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
                ->columns(2)
                ->schema([
                    TextEntry::make('title')->label('Titre')->columnSpanFull(),
                    TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn (CustomRequestStatus $state): string => $state->label()),
                    TextEntry::make('quantity')->label('Quantité'),
                    TextEntry::make('budget_cents')
                        ->label('Budget')
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
                        ->label('Description')
                        ->columnSpanFull(),
                    TextEntry::make('created_at')
                        ->label('Reçue le')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }
}
