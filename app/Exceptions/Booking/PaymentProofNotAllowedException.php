<?php

namespace App\Exceptions\Booking;

/**
 * Payment proof can only be uploaded or replaced while a booking is pending.
 */
class PaymentProofNotAllowedException extends BookingException
{
}
