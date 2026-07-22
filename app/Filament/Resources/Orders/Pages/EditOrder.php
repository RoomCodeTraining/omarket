<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Order $record */
        $record = $this->getRecord();

        $statusActions = collect($record->status->allowedTransitions())
            ->map(function (OrderStatus $status) use ($record): Action {
                return Action::make('status_'.$status->value)
                    ->label($status->label())
                    ->color($status === OrderStatus::Cancelled ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading("Passer en « {$status->label()} » ?")
                    ->modalDescription('Le client sera notifié par e-mail.')
                    ->action(function (UpdateOrderStatus $updateOrderStatus) use ($record, $status): void {
                        $updateOrderStatus->handle($record->fresh(), $status);

                        Notification::make()
                            ->title('Statut : '.$status->label())
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'subtotal_cents',
                            'shipping_cents',
                            'total_cents',
                        ]);

                        $this->redirect(OrderResource::getUrl('edit', ['record' => $record]));
                    });
            })
            ->all();

        return [
            ...$statusActions,
            ViewAction::make()->label('Voir'),
            DeleteAction::make()->label('Supprimer'),
        ];
    }

    protected function afterSave(): void
    {
        /** @var Order $record */
        $record = $this->getRecord();
        $record->recalculateTotals();
    }
}
