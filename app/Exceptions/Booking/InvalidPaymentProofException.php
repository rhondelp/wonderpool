<?php

namespace App\Exceptions\Booking;

/**
 * The uploaded proof passed the type check but could not be read as an image (corrupt or disguised file).
 */
class InvalidPaymentProofException extends BookingException
{
}
