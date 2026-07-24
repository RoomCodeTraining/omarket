<?php

use App\Actions\Cart\AddCargoItemToCart;
use App\Actions\Orders\PlaceCargoCartOrder;
use App\Enums\CargoStatus;
use App\Enums\OrderType;
use App\Models\Cargo;
use App\Models\CargoItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\CargoCart;
use App\Support\SiteSettings;
use Illuminate\Validation\ValidationException;

it('adds cargo items to a mono-cargo cart and checks out as one order', function () {
    $cargo = Cargo::factory()->open()->create();
    $products = Product::factory()->count(2)->create();

    $itemA = CargoItem::factory()->create([
        'cargo_id' => $cargo->id,
        'product_id' => $products[0]->id,
        'quantity_available' => 10,
        'quantity_reserved' => 0,
        'unit_price_cents' => 1000,
    ]);
    $itemB = CargoItem::factory()->create([
        'cargo_id' => $cargo->id,
        'product_id' => $products[1]->id,
        'quantity_available' => 8,
        'quantity_reserved' => 0,
        'unit_price_cents' => 2000,
    ]);

    app(AddCargoItemToCart::class)->handle($itemA, 2);
    app(AddCargoItemToCart::class)->handle($itemB, 1);

    expect(CargoCart::count())->toBe(3)
        ->and(CargoCart::cargoId())->toBe($cargo->id);

    $order = app(PlaceCargoCartOrder::class)->handle([
        'guest_name' => 'Awa Cargo',
        'guest_email' => 'awa.cargo@example.com',
        'create_account' => false,
    ]);

    expect($order->type)->toBe(OrderType::Cargo)
        ->and($order->cargo_id)->toBe($cargo->id)
        ->and($order->items)->toHaveCount(2)
        ->and($order->total_cents)->toBe(4000)
        ->and(CargoCart::count())->toBe(0)
        ->and($itemA->fresh()->quantity_reserved)->toBe(2)
        ->and($itemB->fresh()->quantity_reserved)->toBe(1);
});

it('rejects adding a product from another cargo to the cart', function () {
    $cargoA = Cargo::factory()->open()->create();
    $cargoB = Cargo::factory()->open()->create();
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();

    $itemA = CargoItem::factory()->create([
        'cargo_id' => $cargoA->id,
        'product_id' => $productA->id,
        'quantity_available' => 5,
        'quantity_reserved' => 0,
    ]);
    $itemB = CargoItem::factory()->create([
        'cargo_id' => $cargoB->id,
        'product_id' => $productB->id,
        'quantity_available' => 5,
        'quantity_reserved' => 0,
    ]);

    app(AddCargoItemToCart::class)->handle($itemA, 1);

    expect(fn () => app(AddCargoItemToCart::class)->handle($itemB, 1))
        ->toThrow(ValidationException::class);
});

it('renders arrivals with cargo cards and boutique products', function () {
    $cargo = Cargo::factory()->create(['status' => CargoStatus::Open]);
    $product = Product::factory()->create();
    CargoItem::factory()->create([
        'cargo_id' => $cargo->id,
        'product_id' => $product->id,
        'quantity_available' => 4,
        'quantity_reserved' => 0,
    ]);

    $this->get(route('arrivals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Arrivals/Index')
            ->has('cargos', 1)
            ->where('cargos.0.products_count', 1)
            ->has('products', 1)
            ->where('products.0.cargo_code', $cargo->code));
});

it('lets an authenticated client checkout the cargo cart without a password', function () {
    SiteSettings::set('checkout_requires_account', true);

    $client = User::factory()->create();
    $cargo = Cargo::factory()->open()->create();
    $product = Product::factory()->create();
    $item = CargoItem::factory()->create([
        'cargo_id' => $cargo->id,
        'product_id' => $product->id,
        'quantity_available' => 5,
        'quantity_reserved' => 0,
        'unit_price_cents' => 1500,
    ]);

    $this->actingAs($client)
        ->post(route('cargo-cart.store'), [
            'cargo_item_id' => $item->id,
            'quantity' => 2,
        ])
        ->assertRedirect();

    $this->actingAs($client)
        ->post(route('cargo-cart.checkout'), [
            'create_account' => true,
            'password' => '',
            'notes' => 'Client connecté',
        ])
        ->assertRedirect();

    $order = Order::query()->where('user_id', $client->id)->latest('id')->first();

    expect($order)->not->toBeNull()
        ->and($order->type)->toBe(OrderType::Cargo)
        ->and($order->cargo_id)->toBe($cargo->id)
        ->and($order->guest_email)->toBe($client->email)
        ->and($item->fresh()->quantity_reserved)->toBe(2);
});
