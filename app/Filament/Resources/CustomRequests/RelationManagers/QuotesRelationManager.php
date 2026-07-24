<?php

namespace App\Filament\Resources\CustomRequests\RelationManagers;

use App\Actions\Courses\SendQuote;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class QuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'quotes';

    protected static ?string $title = 'Devis';

    protected static ?string $modelLabel = 'devis';

    protected static ?string $pluralModelLabel = 'devis';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Devis')
                ->description('Créez un brouillon, puis utilisez « Envoyer au client » pour notifier par e-mail.')
                ->icon('heroicon-o-document-text')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('amount_cents')
                        ->label('Montant (cents CAD)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('¢')
                        ->helperText('Ex. 7500 = 75,00 $'),
                    DateTimePicker::make('valid_until')
                        ->label('Valable jusqu’au')
                        ->native(false)
                        ->seconds(false)
                        ->default(now()->addDays(7)),
                    Textarea::make('message')
                        ->label('Message au client')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('amount_cents')
                    ->label('Montant')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (QuoteStatus $state): string => match ($state) {
                        QuoteStatus::Draft => 'gray',
                        QuoteStatus::Sent => 'info',
                        QuoteStatus::Accepted => 'success',
                        QuoteStatus::Rejected => 'danger',
                        QuoteStatus::Expired => 'warning',
                    })
                    ->formatStateUsing(fn (QuoteStatus $state): string => $state->label()),
                TextColumn::make('valid_until')
                    ->label('Valable jusqu’au')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Créé')
                    ->since(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nouveau devis')
                    ->modalHeading('Créer un devis')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status'] = QuoteStatus::Draft->value;

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('send')
                    ->label('Envoyer au client')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Quote $record): bool => $record->status === QuoteStatus::Draft)
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer ce devis ?')
                    ->modalDescription('Le client recevra un e-mail avec le montant du devis. La course doit être validée au préalable.')
                    ->action(function (Quote $record, SendQuote $sendQuote): void {
                        try {
                            $sendQuote->handle($record);
                        } catch (ValidationException $exception) {
                            $message = collect($exception->errors())->flatten()->first();

                            Notification::make()
                                ->title(is_string($message) && $message !== '' ? $message : 'Envoi impossible')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Devis envoyé')
                            ->body('Le client a été notifié par e-mail.')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->label('Modifier')
                    ->visible(fn (Quote $record): bool => $record->status === QuoteStatus::Draft),
                DeleteAction::make()
                    ->label('Supprimer')
                    ->visible(fn (Quote $record): bool => $record->status === QuoteStatus::Draft),
            ])
            ->emptyStateHeading('Aucun devis')
            ->emptyStateDescription('Créez un devis brouillon, puis envoyez-le au client pour le notifier.');
    }
}
