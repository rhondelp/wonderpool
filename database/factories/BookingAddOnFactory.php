<?php

namespace Database\Factories;

use App\Models\AddOn;
use App\Models\Booking;
use App\Models\BookingAddOn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingAddOn>
 */
class BookingAddOnFactory extends Factory
{
    /**
     * Unit price snapshots the add-on's current price.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'add_on_id' => AddOn::factory(),
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price_cents' => fn (array $attributes): int => AddOn::query()->findOrFail($attributes['add_on_id'])->price_cents,
        ];
    }
}
