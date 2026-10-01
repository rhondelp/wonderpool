<?php

namespace App\Exceptions\Booking;

/**
 * No free reference code was found after the maximum number of attempts.
 */
class ReferenceCodeExhaustedException extends BookingException
{
}
