<?php

use App\Models\CargoItem;
use App\Models\CustomRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use Database\Seeders\CatalogSeeder;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

it('adds a product to the cart', function () {
    $product = Product::query()->where('stock_quantity', '>', 0)->firstOrFail();

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertRedirect();

    $this->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->where('count', 2)
            ->has('items', 1));
});

it('reserves a cargo item and creates a linked cargo order', function () {
    $item = CargoItem::query()->whereColumn('quantity_reserved', '<', 'quantity_available')->firstOrFail();
    $before = $item->quantity_reserved;

    $this->post(route('reservations.store'), [
        'cargo_item_id' => $item->id,
        'quantity' => 2,
        'guest_name' => 'Awa Test',
        'guest_email' => 'awa@example.com',
    ])->assertRedirect();

    $reservation = Reservation::query()->first();

    expect($reservation)->not->toBeNull()
        ->and($item->fresh()->quantity_reserved)->toBe($before + 2)
        ->and($reservation->order_id)->not->toBeNull();

    $order = Order::query()->find($reservation->order_id);

    expect($order)->not->toBeNull()
        ->and($order->cargo_id)->toBe($item->cargo_id)
        ->and($order->type->value)->toBe('cargo')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->cargo_item_id)->toBe($item->id);
});

it('submits a custom request', function () {
    $this->post(route('courses.store'), [
        'guest_name' => 'Kofi Mensah',
        'guest_email' => 'kofi@example.com',
        'title' => 'Ignames fraîches',
        'description' => 'Besoin de 5 kg d’ignames pour le week-end.',
        'quantity' => 5,
        'budget' => 40,
    ])->assertRedirect();

    expect(CustomRequest::query()->where('title', 'Ignames fraîches')->exists())->toBeTrue();
});

it('rejects reservation when quantity exceeds remaining', function () {
    $item = CargoItem::query()->firstOrFail();
    $tooMany = $item->quantityRemaining() + 10;

    $this->from(route('arrivals.index'))
        ->post(route('reservations.store'), [
            'cargo_item_id' => $item->id,
            'quantity' => $tooMany,
            'guest_name' => 'Awa Test',
            'guest_email' => 'awa@example.com',
        ])
        ->assertRedirect(route('arrivals.index'))
        ->assertSessionHasErrors('quantity');
});
