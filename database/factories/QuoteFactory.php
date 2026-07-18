<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\CustomRequest;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'custom_request_id' => CustomRequest::factory(),
            'amount_cents' => fake()->numberBetween(2500, 20000),
            'message' => fake()->sentence(),
            'status' => QuoteStatus::Sent,
            'valid_until' => now()->addDays(7),
        ];
    }
}
