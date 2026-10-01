<?php

/*
| M5: PH mobile normalization (D-025).
*/

use App\Support\PhoneNumber;

it('normalizes common Philippine mobile formats to +639XXXXXXXXX', function (string $input) {
    expect(PhoneNumber::normalize($input))->toBe('+639171234567');
})->with([
    '09171234567',
    '0917 123 4567',
    '0917-123-4567',
    '9171234567',
    '639171234567',
    '+639171234567',
    '+63 917 123 4567',
    '(+63) 917-123-4567',
]);

it('rejects numbers that are not PH mobiles', function (?string $input) {
    expect(PhoneNumber::normalize($input))->toBeNull();
})->with([
    '',
    null,
    '0217123456',      // Manila landline
    '0817123456',      // wrong prefix
    '091712345678',    // too long
    '+1 415 555 0100', // foreign
    'call me',
]);

it('formats numbers for display', function () {
    expect(PhoneNumber::display('+639171234567'))->toBe('0917 123 4567')
        ->and(PhoneNumber::display('not a number'))->toBe('not a number');
});
