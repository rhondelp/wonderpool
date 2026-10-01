<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $group = fake()->randomElement(['general', 'booking', 'contact']);

        return [
            'key' => $group.'.'.fake()->unique()->slug(2),
            'value' => fake()->words(3, true),
            'group' => $group,
        ];
    }
}
