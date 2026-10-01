<?php

namespace App\Http\Requests\Admin\Booking;

use App\Http\Requests\Public\QuoteRequest;
use App\Models\Booking;

/**
 * Admin calendar/quote JSON (walk-in and reschedule). Same fields as the public quote plus an
 * optional booking id to leave out of the overlap check; lead time is not applied for admins.
 */
class AdminQuoteRequest extends QuoteRequest
{
    /**
     * Any admin who may create bookings.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Booking::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + ['booking' => ['nullable', 'integer']];
    }

    /**
     * Booking being rescheduled (ignored in the overlap check), if any.
     */
    public function ignoreBookingId(): ?int
    {
        return $this->filled('booking') ? $this->integer('booking') : null;
    }
}
