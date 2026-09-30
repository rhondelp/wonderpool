<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

/**
 * Amenities from PLAN.md §2.1 with heroicon outline names (rendered as <x-heroicon-o-{icon}>).
 */
class AmenitySeeder extends Seeder
{
    /**
     * Upserts by amenity name.
     */
    public function run(): void
    {
        $amenities = [
            ['name' => 'Adult Pool', 'icon' => 'sun', 'description' => 'Spacious swimming pool for adults and teens.'],
            ['name' => 'Kiddie Pool', 'icon' => 'face-smile', 'description' => 'Shallow pool where the little ones can splash safely.'],
            ['name' => 'Air-conditioned Rooms', 'icon' => 'home-modern', 'description' => 'Two AC rooms for resting, changing, or an overnight stay.'],
            ['name' => 'Function Hall', 'icon' => 'building-library', 'description' => 'Covered hall for birthdays, reunions, and company events.'],
            ['name' => 'Billiards', 'icon' => 'trophy', 'description' => 'Billiard table for friendly games between swims.'],
            ['name' => 'Videoke', 'icon' => 'microphone', 'description' => 'Sing your heart out with the videoke machine.'],
            ['name' => 'Garden & Cottages', 'icon' => 'sparkles', 'description' => 'Shaded garden cottages and tables for eating and relaxing.'],
            ['name' => 'Parking', 'icon' => 'truck', 'description' => 'On-site parking for cars and vans.'],
        ];

        foreach ($amenities as $index => $amenity) {
            Amenity::query()->updateOrCreate(
                ['name' => $amenity['name']],
                $amenity + ['is_active' => true, 'sort_order' => $index + 1],
            );
        }
    }
}
