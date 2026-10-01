<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Booking permissions (D-029, answers PLAN.md §14 Q6): owners AND staff may view and run the whole
 * booking lifecycle (approve, reject, cancel, complete, reschedule, notes, payments, walk-ins).
 * Owner only: overriding a booking price. Status rules themselves live in BookingService::TRANSITIONS.
 */
class BookingPolicy
{
    /**
     * Any admin (active users only reach admin routes).
     */
    private function admin(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Bookings list.
     */
    public function viewAny(User $user): bool
    {
        return $this->admin($user);
    }

    /**
     * Booking detail, receipt.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $this->admin($user);
    }

    /**
     * Walk-in / phone booking.
     */
    public function create(User $user): bool
    {
        return $this->admin($user);
    }

    /**
     * Approve, reject, cancel, complete.
     */
    public function changeStatus(User $user, Booking $booking): bool
    {
        return $this->admin($user);
    }

    /**
     * Move to another date/package.
     */
    public function reschedule(User $user, Booking $booking): bool
    {
        return $this->admin($user);
    }

    /**
     * Internal notes.
     */
    public function updateNotes(User $user, Booking $booking): bool
    {
        return $this->admin($user);
    }

    /**
     * Record a payment against the booking.
     */
    public function recordPayment(User $user, Booking $booking): bool
    {
        return $this->admin($user);
    }

    /**
     * Set a price different from the quote (walk-ins). Owner only (D-031).
     */
    public function overridePrice(User $user): bool
    {
        return $user->isOwner();
    }
}
