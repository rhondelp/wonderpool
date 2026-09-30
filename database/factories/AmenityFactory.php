<?php

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Amenity>
 */
class AmenityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$name, $icon] = fake()->randomElement([
            ['Adult Pool', 'sun'],
            ['Kiddie Pool', 'face-smile'],
            ['Function Hall', 'building-library'],
            ['Videoke', 'microphone'],
            ['Billiards', 'trophy'],
            ['Parking', 'truck'],
        ]);

        return [
            'name' => $name,
            'description' => fake()->sentence(10),
            'icon' => $icon,
            'image_path' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
