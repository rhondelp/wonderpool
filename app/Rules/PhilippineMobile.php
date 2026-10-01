<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a Philippine mobile number in any common format (see PhoneNumber::normalize()).
 */
class PhilippineMobile implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || PhoneNumber::normalize($value) === null) {
            $fail('Enter a Philippine mobile number, e.g. 0917 123 4567.');
        }
    }
}
