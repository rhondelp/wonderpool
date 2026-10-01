<?php

namespace App\Exceptions\Booking;

/**
 * A price override needs the owner role and a reason, or the overridden price is invalid.
 */
class PriceOverrideNotAllowedException extends BookingException
{
}
