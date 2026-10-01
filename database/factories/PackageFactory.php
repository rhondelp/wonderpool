<?php

namespace Database\Factories;

use App\Models\Package;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Day-style package (same-day window) by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Day', 'Afternoon', 'Morning', 'Weekday Day']).' Package',
            'code' => strtoupper(fake()->unique()->bothify('PK-??##')),
            'description' => fake()->sentence(12),
            'base_price_cents' => Money::fromPesos(fake()->randomElement([5000, 6000, 7000, 8000])),
            'start_time' => '07:00:00',
            'end_time' => '17:00:00',
            'crosses_midnight' => false,
            'max_pax' => fake()->randomElement([30, 40, 50]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Overnight package (7PM–5AM next day).
     */
    public function overnight(): static
    {
        return $this->state(fn () => [
            'name' => 'Night Package',
            'start_time' => '19:00:00',
            'end_time' => '05:00:00',
            'crosses_midnight' => true,
        ]);
    }

    /**
     * Not bookable.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
