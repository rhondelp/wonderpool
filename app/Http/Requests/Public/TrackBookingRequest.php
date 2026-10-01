<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Track-booking lookup: BOTH the reference code and the mobile number are required (PLAN.md §10).
 * Normalization (uppercase code, +63 phone) happens in GuestBookingService::find() (D-025).
 */
class TrackBookingRequest extends FormRequest
{
    /**
     * Public endpoint.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference.required' => 'Enter your booking reference, e.g. WP-2610-A7K3.',
            'phone.required' => 'Enter the mobile number you used when booking.',
        ];
    }
}
