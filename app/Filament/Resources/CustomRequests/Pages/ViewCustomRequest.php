<?php

namespace App\Filament\Resources\CustomRequests\Pages;

use App\Actions\Courses\AssignCargoToCustomRequest;
use App\Actions\Courses\ValidateCustomRequest;
use App\Filament\Resources\CustomRequests\CustomRequestResource;
use App\Models\Cargo;
use App\Models\CustomRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomRequest extends ViewRecord
{
    protected static string $resource = CustomRequestResource::class;

    protected function getHeaderActions(): array
    {
        /** @var CustomRequest $record */
        $record = $this->getRecord();

        return [
            Action::make('validate')
                ->label('Valider la course')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => ! $record->isValidated())
                ->requiresConfirmation()
                ->modalHeading('Valider cette course ?')
                ->modalDescription('Le client et l’équipe Ôhéfê seront notifiés par e-mail. Vous pourrez ensuite envoyer un devis et associer un cargo.')
                ->action(function (ValidateCustomRequest $action) use ($record): void {
                    $action->handle($record->fresh());

                    Notification::make()
                        ->title('Course validée')
                        ->body('Client et équipe notifiés.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'validated_at']);
                }),
            Action::make('assignCargo')
                ->label('Associer un cargo')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->visible(fn (): bool => $record->isValidated())
                ->form([
                    Select::make('cargo_id')
                        ->label('Cargo')
                        ->options(
                            Cargo::query()
                                ->orderByDesc('estimated_arrival_at')
                                ->get()
                                ->mapWithKeys(fn (Cargo $cargo) => [
                                    $cargo->id => "{$cargo->code} — {$cargo->name} ({$cargo->status->label()})",
                                ]),
                        )
                        ->required()
                        ->searchable()
                        ->default($record->cargo_id),
                ])
                ->action(function (array $data, AssignCargoToCustomRequest $action) use ($record): void {
                    $cargo = Cargo::query()->findOrFail($data['cargo_id']);
                    $action->handle($record->fresh(), $cargo);

                    Notification::make()
                        ->title('Cargo associé')
                        ->body('Le client a été notifié avec les infos cargo.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['cargo_id', 'cargo_assigned_at']);
                }),
            EditAction::make(),
        ];
    }
}
