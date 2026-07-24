<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price_cents' => fake()->numberBetween(499, 4599),
            'stock_quantity' => fake()->numberBetween(0, 80),
            'unit' => fake()->randomElement(['unité', 'kg', 'sachet', 'bouteille', 'boîte']),
            'status' => ProductStatus::Published,
            'image_path' => null,
            'is_featured' => false,
            'listed_in_shop' => true,
            'user_id' => null,
            'cargo_id' => null,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true, 'listed_in_shop' => true]);
    }

    public function listedInShop(): static
    {
        return $this->state(fn () => ['listed_in_shop' => true]);
    }

    public function hiddenFromShop(): static
    {
        return $this->state(fn () => ['listed_in_shop' => false]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::PendingReview]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock_quantity' => 0]);
    }
}
