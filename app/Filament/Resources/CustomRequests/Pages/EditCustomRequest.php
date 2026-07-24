<?php

namespace App\Filament\Resources\CustomRequests\Pages;

use App\Actions\Courses\AssignCargoToCustomRequest;
use App\Actions\Courses\ValidateCustomRequest;
use App\Filament\Resources\CustomRequests\CustomRequestResource;
use App\Models\Cargo;
use App\Models\CustomRequest;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCustomRequest extends EditRecord
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
                ->action(function (ValidateCustomRequest $action) use ($record): void {
                    $action->handle($record->fresh());

                    Notification::make()
                        ->title('Course validée')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'validated_at']);
                }),
            Action::make('assignCargo')
                ->label('Associer un cargo')
                ->icon('heroicon-o-truck')
                ->visible(fn (): bool => $record->isValidated())
                ->form([
                    Select::make('cargo_id')
                        ->label('Cargo')
                        ->options(
                            Cargo::query()
                                ->orderByDesc('estimated_arrival_at')
                                ->get()
                                ->mapWithKeys(fn (Cargo $cargo) => [
                                    $cargo->id => "{$cargo->code} — {$cargo->name}",
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
                        ->title('Cargo associé — client notifié')
                        ->success()
                        ->send();

                    $this->refreshFormData(['cargo_id', 'cargo_assigned_at']);
                }),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
