<?php

use App\Actions\Partners\ApprovePartner;
use App\Actions\Partners\RegisterPartner;
use App\Actions\Products\ApproveProduct;
use App\Actions\Products\CreatePartnerProduct;
use App\Actions\Products\SubmitProductForReview;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Cargo;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PartnerAccountCreated;
use App\Notifications\PartnerApproved;
use App\Notifications\ProductSubmittedForReview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function partnerCargo(): Cargo
{
    return Cargo::factory()->inTransit()->create();
}

it('registers a partner without publish rights', function () {
    Notification::fake();

    $partner = app(RegisterPartner::class)->handle([
        'name' => 'Épicerie Test',
        'email' => 'partner-test@example.com',
        'password' => 'password',
    ]);

    expect($partner->role)->toBe(UserRole::Partner)
        ->and($partner->can_publish)->toBeFalse()
        ->and($partner->canPublishProducts())->toBeFalse();

    Notification::assertSentTo($partner, PartnerAccountCreated::class);
});

it('rejects partner registration when email already exists', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    app(RegisterPartner::class)->handle([
        'name' => 'Autre',
        'email' => 'taken@example.com',
        'password' => 'password',
    ]);
})->throws(ValidationException::class);

it('allows product submit only after partner is approved', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    $partner = User::factory()->partner()->create();
    $cargo = partnerCargo();

    $product = app(CreatePartnerProduct::class)->handle($partner, [
        'category_id' => $category->id,
        'cargo_id' => $cargo->id,
        'name' => 'Attiéké test',
        'description' => 'Test',
        'price_cents' => 1200,
        'stock_quantity' => 10,
        'unit' => 'sachet',
    ]);

    expect($product->status)->toBe(ProductStatus::Draft)
        ->and($product->listed_in_shop)->toBeFalse()
        ->and($product->cargo_id)->toBe($cargo->id);

    expect(fn () => app(SubmitProductForReview::class)->handle($product, $partner))
        ->toThrow(ValidationException::class);

    Notification::assertNothingSent();

    app(ApprovePartner::class)->handle($partner);
    $partner->refresh();

    Notification::assertSentTo($partner, PartnerApproved::class);

    $submitted = app(SubmitProductForReview::class)->handle($product->fresh(), $partner);

    expect($submitted->status)->toBe(ProductStatus::PendingReview);

    Notification::assertSentTo($admin, ProductSubmittedForReview::class);

    $published = app(ApproveProduct::class)->handle($submitted);

    expect($published->status)->toBe(ProductStatus::Published)
        ->and($published->listed_in_shop)->toBeFalse();
});

it('registers a partner via http and opens the dashboard', function () {
    Notification::fake();

    $this->post(route('partner.register.store'), [
        'name' => 'Partenaire HTTP',
        'email' => 'partner-http@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('partner.dashboard'));

    $this->assertAuthenticated();
    expect(auth()->user()?->role)->toBe(UserRole::Partner);

    Notification::assertSentTo(auth()->user(), PartnerAccountCreated::class);

    $this->get(route('partner.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Partner/Dashboard')
            ->where('partner.can_publish', false)
            ->where('stats.total', 0)
            ->has('stats.draft')
            ->has('stats.published'));
});

it('stores a partner product with an uploaded image', function () {
    Storage::fake('public');

    $partner = User::factory()->approvedPartner()->create();
    $category = Category::factory()->create();
    $cargo = partnerCargo();

    $this->actingAs($partner)
        ->post(route('partner.products.store'), [
            'category_id' => $category->id,
            'cargo_id' => $cargo->id,
            'name' => 'Attiéké photo',
            'description' => 'Avec image',
            'price' => 12.5,
            'stock_quantity' => 4,
            'unit' => 'sachet',
            'image' => UploadedFile::fake()->image('attiéké.jpg', 400, 400),
        ])
        ->assertRedirect();

    $product = Product::query()->where('name', 'Attiéké photo')->first();

    expect($product)->not->toBeNull()
        ->and($product->image_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($product->image_path))->toBeTrue();

    $this->actingAs($partner)
        ->get(route('partner.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.total', 1)
            ->where('stats.draft', 1)
            ->where('stats.published', 0));
});

it('stores a partner product using a previously uploaded raw image', function () {
    Storage::fake('public');

    $partner = User::factory()->approvedPartner()->create();
    $category = Category::factory()->create();
    $cargo = partnerCargo();

    $png = UploadedFile::fake()->image('x.png', 80, 80)->getContent();

    $upload = $this->actingAs($partner)->call(
        'POST',
        route('partner.products.image.store'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'image/png',
            'HTTP_ACCEPT' => 'application/json',
        ],
        $png,
    );

    $upload->assertOk()->assertJsonStructure(['path', 'url']);

    $this->actingAs($partner)
        ->post(route('partner.products.store'), [
            'category_id' => $category->id,
            'cargo_id' => $cargo->id,
            'name' => 'Attiéké raw upload',
            'description' => 'Via endpoint image',
            'price' => 9.5,
            'stock_quantity' => 2,
            'unit' => 'sachet',
        ])
        ->assertRedirect();

    $product = Product::query()->where('name', 'Attiéké raw upload')->first();

    expect($product)->not->toBeNull()
        ->and($product->image_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($product->image_path))->toBeTrue();
});

it('stores a partner product using a multipart image pre-upload', function () {
    Storage::fake('public');

    $partner = User::factory()->approvedPartner()->create();
    $category = Category::factory()->create();
    $cargo = partnerCargo();

    $this->actingAs($partner)
        ->post(route('partner.products.image.store'), [
            'image' => UploadedFile::fake()->image('pre.jpg', 200, 200),
        ], [
            'Accept' => 'application/json',
        ])
        ->assertOk()
        ->assertJsonStructure(['path', 'url']);

    $this->actingAs($partner)
        ->post(route('partner.products.store'), [
            'category_id' => $category->id,
            'cargo_id' => $cargo->id,
            'name' => 'Attiéké multipart pre',
            'description' => 'Via multipart image endpoint',
            'price' => 6.5,
            'stock_quantity' => 2,
            'unit' => 'sachet',
        ])
        ->assertRedirect();

    $product = Product::query()->where('name', 'Attiéké multipart pre')->first();

    expect($product)->not->toBeNull()
        ->and($product->image_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($product->image_path))->toBeTrue();
});

it('stores a partner product using chunked json image upload', function () {
    Storage::fake('public');
    Storage::fake('local');

    $partner = User::factory()->approvedPartner()->create();
    $category = Category::factory()->create();
    $cargo = partnerCargo();

    $png = UploadedFile::fake()->image('chunk.png', 120, 120)->getContent();
    $uploadId = (string) Str::uuid();
    $chunks = str_split($png, 64);

    foreach ($chunks as $index => $chunk) {
        $response = $this->actingAs($partner)->postJson(route('partner.products.image.chunk'), [
            'upload_id' => $uploadId,
            'index' => $index,
            'total' => count($chunks),
            'chunk' => base64_encode($chunk),
        ]);

        if ($index === count($chunks) - 1) {
            $response->assertOk()->assertJsonPath('complete', true);
        } else {
            $response->assertOk()->assertJsonPath('complete', false);
        }
    }

    $this->actingAs($partner)
        ->post(route('partner.products.store'), [
            'category_id' => $category->id,
            'cargo_id' => $cargo->id,
            'name' => 'Attiéké chunks',
            'description' => 'Via chunks',
            'price' => 7.5,
            'stock_quantity' => 3,
            'unit' => 'sachet',
        ])
        ->assertRedirect();

    $product = Product::query()->where('name', 'Attiéké chunks')->first();

    expect($product)->not->toBeNull()
        ->and($product->image_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($product->image_path))->toBeTrue();
});

it('stores a partner product with a base64 image in the create payload', function () {
    Storage::fake('public');

    $partner = User::factory()->approvedPartner()->create();
    $category = Category::factory()->create();
    $cargo = partnerCargo();

    $png = base64_encode(UploadedFile::fake()->image('y.png', 60, 60)->getContent());

    $this->actingAs($partner)
        ->post(route('partner.products.store'), [
            'category_id' => $category->id,
            'cargo_id' => $cargo->id,
            'name' => 'Attiéké json image',
            'description' => 'Via image_base64',
            'price' => 8.5,
            'stock_quantity' => 1,
            'unit' => 'sachet',
            'image_base64' => 'data:image/png;base64,'.$png,
        ])
        ->assertRedirect();

    $product = Product::query()->where('name', 'Attiéké json image')->first();

    expect($product)->not->toBeNull()
        ->and($product->image_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($product->image_path))->toBeTrue();
});

it('updates a partner product and can unpublish a published one', function () {
    $partner = User::factory()->approvedPartner()->create();
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'user_id' => $partner->id,
        'cargo_id' => partnerCargo()->id,
        'listed_in_shop' => false,
        'status' => ProductStatus::Published,
        'name' => 'Attiéké publié',
        'slug' => 'attieke-publie',
        'price_cents' => 1000,
        'stock_quantity' => 5,
        'unit' => 'sachet',
    ]);

    $this->actingAs($partner)
        ->put(route('partner.products.update', $product), [
            'category_id' => $otherCategory->id,
            'name' => 'Attiéké modifié',
            'description' => 'Mis à jour',
            'price' => 14.5,
            'stock_quantity' => 8,
            'unit' => 'kg',
        ])
        ->assertRedirect();

    $product->refresh();

    expect($product->name)->toBe('Attiéké modifié')
        ->and($product->category_id)->toBe($otherCategory->id)
        ->and($product->price_cents)->toBe(1450)
        ->and($product->status)->toBe(ProductStatus::Published);

    $this->actingAs($partner)
        ->post(route('partner.products.unpublish', $product))
        ->assertRedirect();

    expect($product->refresh()->status)->toBe(ProductStatus::Archived);
});

it('lists partner orders that include their products', function () {
    $partner = User::factory()->approvedPartner()->create();
    $otherPartner = User::factory()->approvedPartner()->create();
    $category = Category::factory()->create();

    $ownProduct = Product::factory()->create([
        'category_id' => $category->id,
        'user_id' => $partner->id,
        'cargo_id' => partnerCargo()->id,
        'listed_in_shop' => false,
        'status' => ProductStatus::Published,
    ]);

    $otherProduct = Product::factory()->create([
        'category_id' => $category->id,
        'user_id' => $otherPartner->id,
        'cargo_id' => partnerCargo()->id,
        'listed_in_shop' => false,
        'status' => ProductStatus::Published,
    ]);

    $ownOrder = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $ownOrder->id,
        'product_id' => $ownProduct->id,
        'product_name' => $ownProduct->name,
    ]);

    $foreignOrder = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $foreignOrder->id,
        'product_id' => $otherProduct->id,
        'product_name' => $otherProduct->name,
    ]);

    $this->actingAs($partner)
        ->get(route('partner.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Partner/Dashboard')
            ->has('orders', 1)
            ->where('orders.0.reference', $ownOrder->reference));
});

it('only shows published products in the shop', function () {
    $category = Category::factory()->create();
    $partner = User::factory()->approvedPartner()->create();

    Product::factory()->create([
        'category_id' => $category->id,
        'user_id' => $partner->id,
        'cargo_id' => partnerCargo()->id,
        'listed_in_shop' => false,
        'status' => ProductStatus::Published,
        'name' => 'Produit partenaire publié',
        'slug' => 'produit-partenaire-publie',
    ]);

    Product::factory()->create([
        'category_id' => $category->id,
        'user_id' => null,
        'status' => ProductStatus::Published,
        'listed_in_shop' => true,
        'name' => 'Produit public',
        'slug' => 'produit-public',
    ]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->where('products.data', fn ($products) => collect($products)->contains(
                fn ($product) => $product['slug'] === 'produit-public',
            ) && collect($products)->doesntContain(
                fn ($product) => $product['slug'] === 'produit-partenaire-publie',
            )));
});
