<?php

namespace Database\Factories;

use App\Models\Cargo;
use App\Models\CargoItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CargoItem>
 */
class CargoItemFactory extends Factory
{
    protected $model = CargoItem::class;

    public function definition(): array
    {
        $available = fake()->numberBetween(10, 100);

        return [
            'cargo_id' => Cargo::factory(),
            'product_id' => Product::factory(),
            'quantity_available' => $available,
            'quantity_reserved' => fake()->numberBetween(0, (int) ($available / 3)),
            'unit_price_cents' => fake()->numberBetween(799, 5999),
        ];
    }
}
