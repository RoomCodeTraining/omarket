<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Order $record */
        $record = $this->getRecord();

        $statusActions = collect($record->status->allowedTransitions())
            ->map(fn (OrderStatus $status): Action => Action::make('status_'.$status->value)
                ->label($status->label())
                ->color($status === OrderStatus::Cancelled ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function (UpdateOrderStatus $updateOrderStatus) use ($record, $status): void {
                    $updateOrderStatus->handle($record->fresh(), $status);

                    Notification::make()
                        ->title('Statut : '.$status->label())
                        ->success()
                        ->send();

                    $this->redirect(OrderResource::getUrl('view', ['record' => $record]));
                }))
            ->all();

        return [
            ...$statusActions,
            EditAction::make()->label('Gérer'),
        ];
    }
}
