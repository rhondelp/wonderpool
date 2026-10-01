<?php

namespace App\Exceptions\Booking;

/**
 * The start time is too soon (booking.lead_time_hours) or too far ahead (booking.max_advance_days) for a guest booking.
 */
class BookingWindowException extends BookingException
{
}
