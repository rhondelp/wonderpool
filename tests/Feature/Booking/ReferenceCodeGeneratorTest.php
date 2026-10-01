<?php

/*
| M4: ReferenceCodeGenerator — WP-YYMM-XXXX codes, unique across all bookings (D-023).
*/

use App\Exceptions\Booking\ReferenceCodeExhaustedException;
use App\Models\Booking;
use App\Services\Booking\ReferenceCodeGenerator;
use Illuminate\Support\Carbon;

/**
 * Generator whose random source replays $indexes (into the alphabet), then repeats the last one.
 *
 * @param  list<int>  $indexes
 */
function scriptedGenerator(array $indexes): ReferenceCodeGenerator
{
    return new ReferenceCodeGenerator(function () use (&$indexes): int {
        return count($indexes) > 1 ? array_shift($indexes) : $indexes[0];
    });
}

it('formats codes as WP-YYMM-XXXX from the stay month with unambiguous characters', function () {
    $code = app(ReferenceCodeGenerator::class)->generate(Carbon::parse('2026-10-15'));

    expect($code)->toMatch(ReferenceCodeGenerator::pattern())
        ->and($code)->toStartWith('WP-2610-')
        ->and(substr($code, 8))->not->toMatch('/[01OIL]/');
});

it('retries until it finds an unused code, including soft-deleted bookings', function () {
    Booking::factory()->create(['reference_code' => 'WP-2610-AAAA']);
    Booking::factory()->create(['reference_code' => 'WP-2610-BBBB'])->delete();

    // A=0, B=1, C=2 in the alphabet: first try AAAA, then BBBB, then CCCC.
    $generator = scriptedGenerator([0, 0, 0, 0, 1, 1, 1, 1, 2]);

    expect($generator->generate(Carbon::parse('2026-10-01')))->toBe('WP-2610-CCCC');
});

it('gives up after the maximum number of attempts', function () {
    Booking::factory()->create(['reference_code' => 'WP-2610-AAAA']);

    scriptedGenerator([0])->generate(Carbon::parse('2026-10-01'));
})->throws(ReferenceCodeExhaustedException::class);
