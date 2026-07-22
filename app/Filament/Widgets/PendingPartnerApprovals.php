<?php

namespace App\Filament\Widgets;

use App\Actions\Partners\ApprovePartner;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingPartnerApprovals extends TableWidget
{
    protected static ?int $sort = 5;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('File partenaires — comptes à autoriser')
            ->description('Inscriptions partenaires sans droit de publication. Géré séparément du catalogue Ôhéfê.')
            ->query(
                fn (): Builder => User::query()
                    ->partners()
                    ->where('can_publish', false)
                    ->latest(),
            )
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('created_at')->label('Inscrit')->since(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Autoriser à publier')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (User $record, ApprovePartner $approvePartner): void {
                        $approvePartner->handle($record);

                        Notification::make()
                            ->title("{$record->name} autorisé à publier")
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Aucun partenaire en attente')
            ->emptyStateDescription('Les nouvelles inscriptions partenaires apparaîtront ici.');
    }
}
