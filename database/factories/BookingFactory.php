<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Database\Factories\Concerns\PhilippineData;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    use PhilippineData;

    /**
     * Pending booking of a Day package on a future date; the window and price follow the package.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->phName();
        $date = Carbon::instance(fake()->dateTimeBetween('+3 days', '+6 months'))->startOfDay();

        return [
            'reference_code' => fake()->unique()->regexify('WP-[A-HJ-NP-Z2-9]{8}'),
            'package_id' => Package::factory(),
            'guest_name' => $name,
            'guest_phone' => $this->phMobile(),
            'guest_email' => $this->phEmail($name),
            'event_type' => fake()->randomElement(['Birthday', 'Family reunion', 'Company outing', 'Baptism', 'Barkada swimming', null]),
            'guest_count' => fake()->numberBetween(10, 50),
            'notes' => fake()->optional(0.3)->sentence(),
            'starts_at' => $date->copy()->setTime(7, 0),
            'ends_at' => $date->copy()->setTime(17, 0),
            'total_amount_cents' => fn (array $attributes): int => Package::query()->findOrFail($attributes['package_id'])->base_price_cents,
            'downpayment_required_cents' => fn (array $attributes): int => intdiv($attributes['total_amount_cents'], 2),
            'status' => BookingStatus::Pending,
        ];
    }

    /**
     * Book a specific package on a date, resolving its window (handles crosses_midnight).
     */
    public function forPackageOn(Package $package, DateTimeInterface $date): static
    {
        $day = Carbon::instance($date)->startOfDay();
        $start = $day->copy()->setTimeFromTimeString($package->start_time);
        $end = $day->copy()->setTimeFromTimeString($package->end_time);

        if ($package->crosses_midnight) {
            $end->addDay();
        }

        return $this->state(fn () => [
            'package_id' => $package->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'total_amount_cents' => $package->base_price_cents,
            'downpayment_required_cents' => intdiv($package->base_price_cents, 2),
        ]);
    }

    /**
     * Explicit [start, end) window.
     */
    public function window(DateTimeInterface $start, DateTimeInterface $end): static
    {
        return $this->state(fn () => ['starts_at' => $start, 'ends_at' => $end]);
    }

    /**
     * Approved by an owner.
     */
    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Approved,
            'approved_by' => User::factory()->owner(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Rejected with a reason.
     */
    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Rejected,
            'rejection_reason' => 'Payment proof could not be verified.',
        ]);
    }

    /**
     * Cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Cancelled]);
    }

    /**
     * Completed stay in the past.
     */
    public function completed(): static
    {
        return $this->state(function () {
            $date = Carbon::instance(fake()->dateTimeBetween('-6 months', '-2 days'))->startOfDay();

            return [
                'status' => BookingStatus::Completed,
                'starts_at' => $date->copy()->setTime(7, 0),
                'ends_at' => $date->copy()->setTime(17, 0),
                'approved_by' => User::factory()->owner(),
                'approved_at' => $date->copy()->subDays(7),
            ];
        });
    }
}
