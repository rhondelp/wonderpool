<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by PaymentProofService::store() inside its transaction when a guest uploads or replaces
 * a payment proof; listeners run after commit (M8).
 */
class PaymentProofUploaded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Booking  $booking  Booking the proof belongs to
     * @param  Payment  $payment  The pending downpayment holding the proof
     */
    public function __construct(
        public readonly Booking $booking,
        public readonly Payment $payment,
    ) {
    }
}
