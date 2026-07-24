<?php

use App\Models\CargoItem;
use App\Models\CustomRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
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
        'has_supplier' => false,
    ])->assertRedirect();

    $request = CustomRequest::query()->where('title', 'Ignames fraîches')->first();

    expect($request)->not->toBeNull()
        ->and($request->has_supplier)->toBeFalse();
});

it('submits a custom request with a supplier', function () {
    $this->post(route('courses.store'), [
        'guest_name' => 'Awa Fournisseur',
        'guest_email' => 'awa.supplier@example.com',
        'title' => 'Poisson fumé',
        'description' => 'Capitaine fumé du bord de lagune.',
        'quantity' => 3,
        'has_supplier' => true,
        'supplier_name' => 'Marché d’Abobo',
        'supplier_contact' => '+225 07 00 00 00',
    ])->assertRedirect();

    $request = CustomRequest::query()->where('title', 'Poisson fumé')->first();

    expect($request)->not->toBeNull()
        ->and($request->has_supplier)->toBeTrue()
        ->and($request->supplier_name)->toBe('Marché d’Abobo')
        ->and($request->supplier_contact)->toBe('+225 07 00 00 00');
});

it('submits a custom request for an authenticated client without contact fields', function () {
    $client = User::factory()->create([
        'name' => 'Awa Connectée',
        'email' => 'awa.courses@example.com',
    ]);

    $this->actingAs($client)
        ->post(route('courses.store'), [
            'title' => 'Huile de palme',
            'description' => 'Bidon de 5 L, qualité rouge.',
            'quantity' => 2,
            'has_supplier' => false,
        ])
        ->assertRedirect();

    $request = CustomRequest::query()->where('title', 'Huile de palme')->first();

    expect($request)->not->toBeNull()
        ->and($request->user_id)->toBe($client->id)
        ->and($request->guest_name)->toBe('Awa Connectée')
        ->and($request->guest_email)->toBe('awa.courses@example.com')
        ->and($request->has_supplier)->toBeFalse();
});

it('checks out the cart as a guest', function () {
    $product = Product::query()->where('stock_quantity', '>', 2)->firstOrFail();
    $stockBefore = $product->stock_quantity;

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertRedirect();

    $this->post(route('cart.checkout'), [
        'guest_name' => 'Awa Guest',
        'guest_email' => 'awa.guest@example.com',
        'shipping_line1' => '10 rue Saint-Denis',
        'shipping_city' => 'Montréal',
        'shipping_province' => 'Québec',
        'shipping_postal_code' => 'H2X 1Y1',
        'shipping_country' => 'CA',
        'shipping_phone' => '514-555-0101',
        'notes' => 'Livraison le soir',
    ])->assertRedirect();

    $order = Order::query()->where('guest_email', 'awa.guest@example.com')->first();

    expect($order)->not->toBeNull()
        ->and($order->user_id)->toBeNull()
        ->and($order->type->value)->toBe('stock')
        ->and($order->status->value)->toBe('pending_payment')
        ->and($order->shipping_line1)->toBe('10 rue Saint-Denis')
        ->and($order->shipping_city)->toBe('Montréal')
        ->and($order->items)->toHaveCount(1)
        ->and($product->fresh()->stock_quantity)->toBe($stockBefore - 2);

    $this->get(route('orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Orders/Show')
            ->where('order.reference', $order->reference));
});

it('checks out the cart and creates a client account', function () {
    $product = Product::query()->where('stock_quantity', '>', 0)->firstOrFail();

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertRedirect();

    $this->post(route('cart.checkout'), [
        'guest_name' => 'Kofi Client',
        'guest_email' => 'kofi.client@example.com',
        'shipping_line1' => '55 avenue du Parc',
        'shipping_city' => 'Laval',
        'shipping_province' => 'Québec',
        'shipping_postal_code' => 'H7N 2E1',
        'create_account' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $user = User::query()->where('email', 'kofi.client@example.com')->first();
    $order = Order::query()->where('guest_email', 'kofi.client@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role->value)->toBe('client')
        ->and($order)->not->toBeNull()
        ->and($order->user_id)->toBe($user->id)
        ->and(auth()->id())->toBe($user->id);
});

it('attaches checkout to the authenticated client', function () {
    $client = User::factory()->create([
        'name' => 'Client Connecté',
        'email' => 'client.connected@example.com',
    ]);
    $product = Product::query()->where('stock_quantity', '>', 0)->firstOrFail();

    $this->actingAs($client)
        ->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($client)
        ->post(route('cart.checkout'), [
            'shipping_line1' => '200 boulevard René-Lévesque',
            'shipping_city' => 'Québec',
            'shipping_province' => 'Québec',
            'shipping_postal_code' => 'G1R 2B5',
            'notes' => 'Compte existant',
        ])
        ->assertRedirect();

    $order = Order::query()->where('user_id', $client->id)->latest('id')->first();

    expect($order)->not->toBeNull()
        ->and($order->guest_name)->toBe('Client Connecté')
        ->and($order->guest_email)->toBe('client.connected@example.com')
        ->and($order->shipping_city)->toBe('Québec');
});

it('shows account orders and custom requests for a connected client', function () {
    $client = User::factory()->create([
        'name' => 'Client Compte',
        'email' => 'client.compte@example.com',
    ]);

    $product = Product::query()->where('stock_quantity', '>', 0)->firstOrFail();

    $order = Order::factory()->create([
        'user_id' => $client->id,
        'guest_name' => $client->name,
        'guest_email' => $client->email,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
    ]);

    CustomRequest::factory()->create([
        'user_id' => $client->id,
        'guest_name' => $client->name,
        'guest_email' => $client->email,
        'title' => 'Demande attieke',
    ]);

    $this->actingAs($client)
        ->get(route('account.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Account/Index')
            ->has('orders', 1)
            ->where('orders.0.reference', $order->reference)
            ->has('custom_requests', 1)
            ->where('custom_requests.0.title', 'Demande attieke'));
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
