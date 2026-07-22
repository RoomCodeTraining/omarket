<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2000, 25000);

        return [
            'user_id' => User::factory(),
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'type' => OrderType::Stock,
            'status' => OrderStatus::PaymentConfirmed,
            'subtotal_cents' => $subtotal,
            'shipping_cents' => 0,
            'total_cents' => $subtotal,
            'currency' => 'CAD',
            'notes' => null,
            'placed_at' => now(),
        ];
    }
}
