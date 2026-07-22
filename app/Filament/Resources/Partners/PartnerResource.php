<?php

namespace App\Filament\Resources\Partners;

use App\Actions\Partners\ApprovePartner;
use App\Actions\Partners\RevokePartnerPublish;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartnerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Comptes partenaires';

    protected static ?string $modelLabel = 'partenaire';

    protected static ?string $pluralModelLabel = 'partenaires';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|\UnitEnum|null $navigationGroup = 'Partenaires';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->partners()->latest();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                IconColumn::make('can_publish')->label('Peut publier')->boolean(),
                TextColumn::make('partner_approved_at')
                    ->label('Autorisé le')
                    ->dateTime('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('created_at')->label('Inscrit le')->dateTime('d/m/Y')->sortable(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Autoriser à publier')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => ! $record->can_publish)
                    ->requiresConfirmation()
                    ->action(function (User $record, ApprovePartner $approvePartner): void {
                        $approvePartner->handle($record);

                        Notification::make()
                            ->title('Partenaire autorisé à publier')
                            ->success()
                            ->send();
                    }),
                Action::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->can_publish)
                    ->requiresConfirmation()
                    ->action(function (User $record, RevokePartnerPublish $revokePartner): void {
                        $revokePartner->handle($record);

                        Notification::make()
                            ->title('Droit de publication révoqué')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
