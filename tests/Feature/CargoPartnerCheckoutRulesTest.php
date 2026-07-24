<?php

use App\Actions\Cargos\GenerateCargoCode;
use App\Actions\Orders\PlaceCartOrder;
use App\Actions\Partners\NotifyPartnersOfUpcomingCargoDeposit;
use App\Actions\Products\CreatePartnerProduct;
use App\Actions\Products\RecordPartnerWarehouseDeposit;
use App\Enums\ProductStatus;
use App\Models\Cargo;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PartnerDepositReminder;
use App\Notifications\PartnerProductDeposited;
use App\Support\Cart;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('generates unique cargo codes automatically', function () {
    $codeA = app(GenerateCargoCode::class)->handle(now());
    Cargo::factory()->create(['code' => $codeA]);

    $codeB = app(GenerateCargoCode::class)->handle(now());

    expect($codeA)->toStartWith('CG-')
        ->and($codeB)->not->toBe($codeA);
});

it('rejects partner products when no cargo is in transit', function () {
    $partner = User::factory()->approvedPartner()->create();
    $category = Category::factory()->create();
    $cargo = Cargo::factory()->open()->create();

    expect(fn () => app(CreatePartnerProduct::class)->handle($partner, [
        'category_id' => $category->id,
        'cargo_id' => $cargo->id,
        'name' => 'Produit trop tôt',
        'price_cents' => 1000,
        'stock_quantity' => 2,
        'unit' => 'sachet',
    ]))->toThrow(ValidationException::class);
});

it('links partner products to cargo and keeps them out of the shop', function () {
    $partner = User::factory()->approvedPartner()->create();
    $category = Category::factory()->create();
    $cargo = Cargo::factory()->inTransit()->create();

    $product = app(CreatePartnerProduct::class)->handle($partner, [
        'category_id' => $category->id,
        'cargo_id' => $cargo->id,
        'name' => 'Produit cargo',
        'price_cents' => 1500,
        'stock_quantity' => 5,
        'unit' => 'sachet',
    ]);

    expect($product->listed_in_shop)->toBeFalse()
        ->and($product->cargo_id)->toBe($cargo->id)
        ->and($product->cargoItems()->count())->toBe(1);

    $shopProduct = Product::factory()->create([
        'status' => ProductStatus::Published,
        'listed_in_shop' => true,
    ]);
    $hidden = Product::factory()->create([
        'status' => ProductStatus::Published,
        'listed_in_shop' => false,
    ]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->has('products.data', 1)
            ->where('products.data.0.id', $shopProduct->id));

    expect(Product::query()->published()->listedInShop()->pluck('id')->all())
        ->toContain($shopProduct->id)
        ->not->toContain($hidden->id)
        ->not->toContain($product->id);
});

it('requires an account to checkout when setting is enabled', function () {
    SiteSettings::set('checkout_requires_account', true);

    $product = Product::factory()->create([
        'status' => ProductStatus::Published,
        'listed_in_shop' => true,
        'stock_quantity' => 3,
        'price_cents' => 1000,
    ]);

    Cart::put([
        $product->id => ['product_id' => $product->id, 'quantity' => 1],
    ]);

    expect(fn () => app(PlaceCartOrder::class)->handle([
        'guest_name' => 'Invité',
        'guest_email' => 'invite@example.com',
        'shipping_line1' => '1 rue Test',
        'shipping_city' => 'Montréal',
        'shipping_province' => 'Québec',
        'shipping_postal_code' => 'H2X1Y4',
        'shipping_country' => 'CA',
        'create_account' => false,
    ]))->toThrow(ValidationException::class);

    SiteSettings::set('checkout_requires_account', false);
});

it('notifies partners three days before cargo eta when orders exist', function () {
    Notification::fake();
    SiteSettings::set('partner_deposit_reminder_days', 3);

    $partner = User::factory()->approvedPartner()->create();
    $cargo = Cargo::factory()->inTransit()->create([
        'estimated_arrival_at' => now()->addDays(3)->toDateString(),
    ]);
    $product = Product::factory()->create([
        'user_id' => $partner->id,
        'cargo_id' => $cargo->id,
        'listed_in_shop' => false,
        'status' => ProductStatus::Published,
    ]);
    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $count = app(NotifyPartnersOfUpcomingCargoDeposit::class)->handle();

    expect($count)->toBe(1);
    Notification::assertSentTo($partner, PartnerDepositReminder::class);
    expect($product->fresh()->deposit_reminder_sent_at)->not->toBeNull();
});

it('records warehouse deposit as admin with quantity visible to partner', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $partner = User::factory()->approvedPartner()->create();
    $cargo = Cargo::factory()->inTransit()->create();
    $product = Product::factory()->create([
        'user_id' => $partner->id,
        'cargo_id' => $cargo->id,
        'listed_in_shop' => false,
        'stock_quantity' => 12,
    ]);

    $updated = app(RecordPartnerWarehouseDeposit::class)->handle($product, $admin, 7);

    expect($updated->warehouse_deposited_at)->not->toBeNull()
        ->and($updated->warehouse_deposited_quantity)->toBe(7)
        ->and($updated->isWarehouseDeposited())->toBeTrue();

    Notification::assertSentTo($partner, PartnerProductDeposited::class);

    expect(fn () => app(RecordPartnerWarehouseDeposit::class)->handle($product, $partner, 3))
        ->toThrow(ValidationException::class);
});
