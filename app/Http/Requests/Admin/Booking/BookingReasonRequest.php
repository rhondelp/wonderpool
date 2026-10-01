<?php

namespace App\Http\Requests\Admin\Booking;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Approve / reject / cancel / complete. A reason is required to reject and optional to cancel;
 * the status rules themselves are enforced by BookingService::TRANSITIONS.
 */
class BookingReasonRequest extends FormRequest
{
    /**
     * Delegates to BookingPolicy::changeStatus.
     */
    public function authorize(): bool
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        return $this->user()?->can('changeStatus', $booking) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reason' => [$this->routeIs('admin.bookings.reject') ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['reason.required' => 'Please give the guest a reason for the rejection.'];
    }

    /**
     * Trimmed reason or null.
     */
    public function reason(): ?string
    {
        $reason = trim((string) $this->input('reason'));

        return $reason === '' ? null : $reason;
    }
}
