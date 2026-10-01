<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Pending GCash downpayment for the booking's required downpayment.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'type' => PaymentType::Downpayment,
            'amount_cents' => fn (array $attributes): int => Booking::query()->findOrFail($attributes['booking_id'])->downpayment_required_cents,
            'proof_path' => 'payment-proofs/'.fake()->uuid().'.jpg',
            'status' => PaymentStatus::Pending,
            'reference_no' => fake()->numerify('#############'),
        ];
    }

    /**
     * Verified by an owner.
     */
    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Verified,
            'verified_by' => User::factory()->owner(),
            'verified_at' => now(),
        ]);
    }

    /**
     * Rejected proof.
     */
    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Rejected,
            'verified_by' => User::factory()->owner(),
            'verified_at' => now(),
        ]);
    }

    /**
     * Cash balance recorded at the front desk (no proof image).
     */
    public function cashBalance(): static
    {
        return $this->state(fn () => [
            'type' => PaymentType::Balance,
            'proof_path' => null,
            'reference_no' => null,
        ]);
    }
}
