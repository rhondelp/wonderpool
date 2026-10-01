<?php

namespace App\Http\Requests\Admin\Booking;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Reject a pending payment or void a verified one (owner only, PaymentPolicy::reject).
 */
class RejectPaymentRequest extends FormRequest
{
    /**
     * Delegates to PaymentPolicy::reject.
     */
    public function authorize(): bool
    {
        /** @var Payment $payment */
        $payment = $this->route('payment');

        return $this->user()?->can('reject', $payment) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['reason.required' => 'Please give a reason (the guest sees it on the tracking page).'];
    }
}
