<?php

namespace App\Exceptions\Booking;

/**
 * The payment cannot be changed this way (e.g. already decided, or voiding a verified payment without the owner role).
 */
class PaymentActionNotAllowedException extends BookingException
{
}
