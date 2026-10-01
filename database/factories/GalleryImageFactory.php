<?php

namespace Database\Factories;

use App\Enums\GalleryCategory;
use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => 'gallery/'.fake()->uuid().'.jpg',
            'caption' => fake()->randomElement(['Sunset by the adult pool', 'Kids enjoying the kiddie pool', 'Function hall set up for a debut', 'Garden cottages', 'Family reunion lunch']),
            'category' => fake()->randomElement(GalleryCategory::cases()),
            'is_visible' => true,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    /**
     * Hidden from the public gallery.
     */
    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }
}
