<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * An owner approving a booking.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->owner(),
            'action' => 'booking.approved',
            'subject_type' => Booking::class,
            'subject_id' => Booking::factory(),
            'properties' => ['from' => 'pending', 'to' => 'approved'],
        ];
    }
}
