<?php

namespace Database\Factories;

use App\Models\AddOn;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AddOn>
 */
class AddOnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$name, $pesos] = fake()->randomElement([
            ['Extra hour', 1000],
            ['Extra 10 pax', 1500],
            ['Cottage rental', 500],
            ['Videoke machine', 800],
            ['Lechon table setup', 1200],
            ['Extra AC room', 2000],
        ]);

        return [
            'name' => $name,
            'description' => fake()->sentence(),
            'price_cents' => Money::fromPesos($pesos),
            'is_active' => true,
        ];
    }

    /**
     * Not offered.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
