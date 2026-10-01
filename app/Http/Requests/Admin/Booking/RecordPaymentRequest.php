<?php

namespace App\Http\Requests\Admin\Booking;

use App\Enums\PaymentType;
use App\Models\Booking;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Record a payment received outside the website (amount in pesos → centavos, D-001).
 */
class RecordPaymentRequest extends FormRequest
{
    /**
     * Delegates to BookingPolicy::recordPayment.
     */
    public function authorize(): bool
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        return $this->user()?->can('recordPayment', $booking) ?? false;
    }

    /**
     * @return array<string, list<string|ValidationRule|Enum>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PaymentType::class)],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999.99'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Amount in centavos.
     */
    public function amountCents(): int
    {
        return Money::fromPesos((string) $this->validated('amount'));
    }
}
