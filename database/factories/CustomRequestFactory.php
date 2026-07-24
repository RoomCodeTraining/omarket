<?php

namespace Database\Factories;

use App\Enums\CustomRequestStatus;
use App\Models\CustomRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomRequest>
 */
class CustomRequestFactory extends Factory
{
    protected $model = CustomRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'quantity' => fake()->numberBetween(1, 10),
            'budget_cents' => fake()->optional()->numberBetween(2000, 15000),
            'has_supplier' => false,
            'supplier_name' => null,
            'supplier_contact' => null,
            'cargo_id' => null,
            'status' => CustomRequestStatus::Submitted,
            'validated_at' => null,
            'cargo_assigned_at' => null,
        ];
    }

    public function withSupplier(): static
    {
        return $this->state(fn () => [
            'has_supplier' => true,
            'supplier_name' => fake()->company(),
            'supplier_contact' => fake()->phoneNumber(),
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn () => [
            'status' => CustomRequestStatus::InReview,
            'validated_at' => now(),
        ]);
    }
}
