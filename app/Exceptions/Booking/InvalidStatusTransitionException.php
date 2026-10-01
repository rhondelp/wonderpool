<?php

namespace App\Exceptions\Booking;

/**
 * A status change is not allowed by BookingService::TRANSITIONS (or its preconditions are not met).
 */
class InvalidStatusTransitionException extends BookingException
{
}
