<?php

namespace Database\Factories;

use App\Models\CustomRequest;
use App\Models\CustomRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomRequestItem>
 */
class CustomRequestItemFactory extends Factory
{
    protected $model = CustomRequestItem::class;

    public function definition(): array
    {
        return [
            'custom_request_id' => CustomRequest::factory(),
            'label' => fake()->words(3, true),
            'quantity' => fake()->numberBetween(1, 10),
            'budget_cents' => fake()->optional()->numberBetween(2000, 15000),
            'sort_order' => 0,
        ];
    }
}
