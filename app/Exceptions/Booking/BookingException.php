<?php

namespace App\Exceptions\Booking;

use RuntimeException;

/**
 * Base class for booking-engine rule violations. Messages are safe to show to admins and guests.
 */
abstract class BookingException extends RuntimeException
{
}
