<?php

namespace Database\Factories;

use App\Enums\CargoStatus;
use App\Models\Cargo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cargo>
 */
class CargoFactory extends Factory
{
    protected $model = Cargo::class;

    public function definition(): array
    {
        $departure = now()->addDays(fake()->numberBetween(3, 20));

        return [
            'code' => 'CG-'.fake()->unique()->numerify('####'),
            'name' => 'Cargo '.fake()->monthName(),
            'origin' => 'Abidjan',
            'destination' => fake()->randomElement(['Montréal', 'Toronto', 'Ottawa']),
            'status' => CargoStatus::Open,
            'departure_at' => $departure,
            'estimated_arrival_at' => (clone $departure)->addDays(18),
            'notes' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => CargoStatus::Open]);
    }
}
