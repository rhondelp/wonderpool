<?php

namespace App\Http\Requests\Admin\Booking;

use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Package;
use App\Rules\PhilippineMobile;
use App\Support\Money;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Walk-in / phone booking by an admin (D-031): guest details, optional payment received now,
 * optional immediate approval, and an owner-only price override with a mandatory reason.
 * A staff user who sends an override gets 403.
 */
class StoreWalkInRequest extends FormRequest
{
    /**
     * BookingPolicy::create, plus BookingPolicy::overridePrice when an override is sent.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->can('create', Booking::class)) {
            return false;
        }

        return ! $this->filled('price_override') || $user->can('overridePrice', Booking::class);
    }

    /**
     * Normalizes the approve checkbox.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['approve' => $this->boolean('approve')]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', Rule::exists(Package::class, 'id')->where('is_active', true)],
            'date' => ['required', 'date_format:Y-m-d'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'add_ons' => ['nullable', 'array', 'max:50'],
            'add_ons.*' => ['nullable', 'integer', 'min:0', 'max:99'],
            'guest_name' => ['required', 'string', 'min:2', 'max:100'],
            'guest_phone' => ['required', 'string', 'max:20', new PhilippineMobile()],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'payment_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999.99'],
            'payment_type' => ['nullable', Rule::enum(PaymentType::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'approve' => ['boolean'],
            'price_override' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999.99'],
            'price_override_reason' => ['nullable', 'required_with:price_override', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['price_override_reason.required_with' => 'A reason is required when overriding the price.'];
    }

    /**
     * Input for BookingAdminService::createWalkIn().
     *
     * @return array<string, mixed>
     */
    public function walkInData(): array
    {
        $data = $this->validated();
        $addOns = [];
        foreach ((array) ($data['add_ons'] ?? []) as $id => $qty) {
            if ((int) $qty > 0) {
                $addOns[(int) $id] = (int) $qty;
            }
        }

        return [
            'package_id' => (int) $data['package_id'],
            'date' => (string) $data['date'],
            'guest_count' => (int) $data['guest_count'],
            'add_ons' => $addOns,
            'guest_name' => trim((string) $data['guest_name']),
            'guest_phone' => (string) PhoneNumber::normalize((string) $data['guest_phone']),
            'guest_email' => $data['guest_email'] ?? null,
            'event_type' => $data['event_type'] ?? null,
            'notes' => $data['notes'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
            'payment_amount_cents' => isset($data['payment_amount']) ? Money::fromPesos((string) $data['payment_amount']) : 0,
            'payment_type' => PaymentType::tryFrom((string) ($data['payment_type'] ?? '')) ?? PaymentType::Downpayment,
            'payment_reference' => $data['payment_reference'] ?? null,
            'approve' => (bool) $data['approve'],
            'price_override_cents' => isset($data['price_override']) ? Money::fromPesos((string) $data['price_override']) : null,
            'price_override_reason' => $data['price_override_reason'] ?? null,
        ];
    }
}
