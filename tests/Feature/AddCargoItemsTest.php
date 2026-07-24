<?php

use App\Actions\Cargos\AddCargoItems;
use App\Models\Cargo;
use App\Models\CargoItem;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

it('adds multiple cargo items from repeater lines', function () {
    $cargo = Cargo::factory()->create();
    $products = Product::factory()->count(2)->create();

    $items = app(AddCargoItems::class)->handle($cargo, [
        [
            'product_id' => $products[0]->id,
            'quantity_available' => 10,
            'unit_price_cents' => 1500,
        ],
        [
            'product_id' => $products[1]->id,
            'quantity_available' => 5,
            'unit_price_cents' => 2200,
        ],
    ]);

    expect($items)->toHaveCount(2)
        ->and(CargoItem::query()->where('cargo_id', $cargo->id)->count())->toBe(2)
        ->and(CargoItem::query()->where('product_id', $products[0]->id)->value('quantity_available'))->toBe(10);
});

it('rejects duplicate products in the same batch', function () {
    $cargo = Cargo::factory()->create();
    $product = Product::factory()->create();

    app(AddCargoItems::class)->handle($cargo, [
        [
            'product_id' => $product->id,
            'quantity_available' => 2,
            'unit_price_cents' => 1000,
        ],
        [
            'product_id' => $product->id,
            'quantity_available' => 3,
            'unit_price_cents' => 1000,
        ],
    ]);
})->throws(ValidationException::class);
