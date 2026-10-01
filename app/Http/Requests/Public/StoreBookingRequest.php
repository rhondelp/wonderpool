<?php

namespace App\Http\Requests\Public;

use App\Rules\PhilippineMobile;
use App\Rules\Turnstile;
use App\Support\PhoneNumber;

/**
 * Guest booking submission (POST /book). Extends the quote fields with guest details,
 * the honeypot field "website" (must stay empty), terms acceptance and Turnstile (D-028).
 * The phone is normalized to +639XXXXXXXXX after validation (D-025).
 */
class StoreBookingRequest extends QuoteRequest
{
    /** Hidden field bots tend to fill in. */
    public const HONEYPOT = 'website';

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'guest_name' => ['required', 'string', 'min:2', 'max:100'],
            'guest_phone' => ['required', 'string', 'max:20', new PhilippineMobile()],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
            self::HONEYPOT => ['nullable', 'max:0'],
            'cf-turnstile-response' => [new Turnstile($this->ip())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'guest_name.required' => 'Please enter the name of the person booking.',
            'guest_phone.required' => 'Please enter your mobile number so we can confirm your booking.',
            'terms.accepted' => 'Please confirm that you have read the house rules and payment instructions.',
            self::HONEYPOT.'.max' => 'Something went wrong. Please try again.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['guest_name' => 'name', 'guest_phone' => 'mobile number', 'guest_email' => 'email', 'guest_count' => 'number of guests'];
    }

    /**
     * Input for GuestBookingService::book(): normalized phone and add-ons with qty ≥ 1.
     *
     * @return array{package_id: int, date: string, guest_name: string, guest_phone: string, guest_email: ?string, event_type: ?string, guest_count: int, notes: ?string, add_ons: array<int, int>}
     */
    public function bookingData(): array
    {
        $data = $this->validated();

        return [
            'package_id' => (int) $data['package_id'],
            'date' => (string) $data['date'],
            'guest_name' => trim((string) $data['guest_name']),
            'guest_phone' => (string) PhoneNumber::normalize((string) $data['guest_phone']),
            'guest_email' => isset($data['guest_email']) ? (string) $data['guest_email'] : null,
            'event_type' => isset($data['event_type']) ? trim((string) $data['event_type']) : null,
            'guest_count' => (int) $data['guest_count'],
            'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
            'add_ons' => $this->addOns(),
        ];
    }
}
