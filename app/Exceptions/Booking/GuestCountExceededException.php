<?php

namespace App\Exceptions\Booking;

/**
 * The guest count is below 1 or above the package's max_pax.
 */
class GuestCountExceededException extends BookingException
{
}
