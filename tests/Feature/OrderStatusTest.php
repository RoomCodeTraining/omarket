<?php

use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('advances an order status and notifies the customer', function () {
    Notification::fake();

    $client = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $client->id,
        'status' => OrderStatus::PaymentConfirmed,
    ]);

    $updated = app(UpdateOrderStatus::class)->handle($order, OrderStatus::Preparing);

    expect($updated->status)->toBe(OrderStatus::Preparing);

    Notification::assertSentTo($client, OrderStatusUpdated::class);
});

it('rejects an invalid order status jump', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::PaymentConfirmed,
    ]);

    app(UpdateOrderStatus::class)->handle($order, OrderStatus::Delivered);
})->throws(ValidationException::class);
