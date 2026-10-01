<?php

namespace App\Exceptions\Booking;

/**
 * Verified payments do not cover the required downpayment, so the booking cannot be approved yet.
 */
class DownpaymentNotCoveredException extends BookingException
{
}
