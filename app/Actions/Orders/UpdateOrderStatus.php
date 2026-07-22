<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class UpdateOrderStatus
{
    public function handle(Order $order, OrderStatus $nextStatus, bool $notify = true): Order
    {
        if ($order->status === $nextStatus) {
            return $order;
        }

        if (! $order->status->canTransitionTo($nextStatus)) {
            throw ValidationException::withMessages([
                'status' => "Transition impossible : {$order->status->label()} → {$nextStatus->label()}.",
            ]);
        }

        $order = DB::transaction(function () use ($order, $nextStatus) {
            $order->forceFill([
                'status' => $nextStatus,
            ])->save();

            return $order->refresh()->loadMissing('user');
        });

        if ($notify) {
            $this->notifyCustomer($order);
        }

        return $order;
    }

    private function notifyCustomer(Order $order): void
    {
        $user = $order->user;

        if ($user) {
            $user->notify(new OrderStatusUpdated($order));

            return;
        }

        if (filled($order->guest_email)) {
            Notification::route('mail', $order->guest_email)
                ->notify(new OrderStatusUpdated($order));
        }
    }
}
