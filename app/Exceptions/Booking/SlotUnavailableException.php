<?php

namespace App\Exceptions\Booking;

/**
 * The requested time window overlaps an active booking or a blocked date (or the overlap constraint fired).
 */
class SlotUnavailableException extends BookingException
{
}
