<?php

namespace App\Http\Requests\Admin\Booking;

use App\Models\Booking;
use App\Models\Package;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Move a booking to another date (and optionally package); availability is re-checked by BookingService.
 */
class RescheduleBookingRequest extends FormRequest
{
    /**
     * Delegates to BookingPolicy::reschedule.
     */
    public function authorize(): bool
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        return $this->user()?->can('reschedule', $booking) ?? false;
    }

    /**
     * Normalizes the reprice checkbox.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['reprice' => $this->boolean('reprice')]);
    }

    /**
     * @return array<string, list<string|ValidationRule|Exists>>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', Rule::exists(Package::class, 'id')->where('is_active', true)],
            'date' => ['required', 'date_format:Y-m-d'],
            'reprice' => ['boolean'],
        ];
    }
}
