<?php

namespace Database\Factories;

use App\Models\BlockedDate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<BlockedDate>
 */
class BlockedDateFactory extends Factory
{
    /**
     * Whole-day block on a future date.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $day = Carbon::instance(fake()->dateTimeBetween('+1 week', '+4 months'))->startOfDay();

        return [
            'starts_at' => $day,
            'ends_at' => $day->copy()->addDay(),
            'reason' => fake()->randomElement(['Pool cleaning and maintenance', 'Private family event', 'Electrical repairs', 'Town fiesta closure']),
            'created_by' => User::factory()->owner(),
        ];
    }
}
