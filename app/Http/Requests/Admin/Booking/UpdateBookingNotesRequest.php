<?php

namespace App\Http\Requests\Admin\Booking;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Internal admin notes on a booking (never shown to guests).
 */
class UpdateBookingNotesRequest extends FormRequest
{
    /**
     * Delegates to BookingPolicy::updateNotes.
     */
    public function authorize(): bool
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        return $this->user()?->can('updateNotes', $booking) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['admin_notes' => ['nullable', 'string', 'max:5000']];
    }
}
