<?php

namespace Database\Factories;

use App\Enums\CustomRequestStatus;
use App\Models\CustomRequest;
use App\Models\CustomRequestItem;
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
        $label = fake()->sentence(3);
        $quantity = fake()->numberBetween(1, 10);
        $budget = fake()->optional()->numberBetween(2000, 15000);

        return [
            'user_id' => User::factory(),
            'title' => $label,
            'description' => "{$label} × {$quantity}",
            'quantity' => $quantity,
            'budget_cents' => $budget,
            'has_supplier' => false,
            'supplier_name' => null,
            'supplier_contact' => null,
            'cargo_id' => null,
            'status' => CustomRequestStatus::Submitted,
            'validated_at' => null,
            'cargo_assigned_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (CustomRequest $request): void {
            if ($request->items()->exists()) {
                return;
            }

            CustomRequestItem::factory()->create([
                'custom_request_id' => $request->id,
                'label' => $request->title,
                'quantity' => $request->quantity,
                'budget_cents' => $request->budget_cents,
                'sort_order' => 0,
            ]);
        });
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
