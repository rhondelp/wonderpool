<?php

/*
| M4: AvailabilityService — window resolution and half-open exclusive-use checks (PLAN.md §5.1).
| Dates use 2027-03-10 (a Wednesday) to stay clear of factory defaults.
*/

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Services\Booking\AvailabilityService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->availability = app(AvailabilityService::class);
    $this->day = dayPackage();
    $this->night = nightPackage();
    $this->full = fullDayPackage();
});

/**
 * Books a package on a date through the factory using the service's own window.
 */
function bookPackage(App\Models\Package $package, string $date, string $state = 'pending'): Booking
{
    $window = app(AvailabilityService::class)->resolveWindow($package, Carbon::parse($date));
    $factory = Booking::factory()->for($package)->window($window['starts_at'], $window['ends_at']);

    return match ($state) {
        'approved' => $factory->approved()->create(),
        'cancelled' => $factory->cancelled()->create(),
        'rejected' => $factory->rejected()->create(),
        default => $factory->create(),
    };
}

/**
 * Whether a package is free on a date.
 */
function packageFree(App\Models\Package $package, string $date, ?int $ignore = null): bool
{
    $window = app(AvailabilityService::class)->resolveWindow($package, Carbon::parse($date));

    return app(AvailabilityService::class)->isAvailable($window['starts_at'], $window['ends_at'], $ignore);
}

it('resolves package windows, crossing midnight when needed', function (string $package, string $start, string $end) {
    $window = $this->availability->resolveWindow($this->{$package}, Carbon::parse('2027-03-10 15:30'));

    expect($window['starts_at']->format('Y-m-d H:i'))->toBe($start)
        ->and($window['ends_at']->format('Y-m-d H:i'))->toBe($end)
        ->and($window['starts_at']->getTimezone()->getName())->toBe('Asia/Manila');
})->with([
    'Day' => ['day', '2027-03-10 07:00', '2027-03-10 17:00'],
    'Night (crosses midnight)' => ['night', '2027-03-10 19:00', '2027-03-11 05:00'],
    '24-Hour (crosses midnight)' => ['full', '2027-03-10 07:00', '2027-03-11 05:00'],
]);

it('resolves windows across month and year ends', function () {
    $window = $this->availability->resolveWindow($this->night, Carbon::parse('2027-12-31'));

    expect($window['ends_at']->format('Y-m-d H:i'))->toBe('2028-01-01 05:00');
});

it('allows a Night after a Day on the same date (back-to-back windows)', function () {
    bookPackage($this->day, '2027-03-10');

    expect(packageFree($this->night, '2027-03-10'))->toBeTrue();
});

it('allows a Day after the previous Night ends at 05:00', function () {
    bookPackage($this->night, '2027-03-09');

    expect(packageFree($this->day, '2027-03-10'))->toBeTrue();
});

it('makes a 24-Hour booking conflict with both Day and Night', function () {
    bookPackage($this->full, '2027-03-10');

    expect(packageFree($this->day, '2027-03-10'))->toBeFalse()
        ->and(packageFree($this->night, '2027-03-10'))->toBeFalse()
        ->and(packageFree($this->day, '2027-03-11'))->toBeTrue();
});

it('blocks a 24-Hour booking when either a Day or a Night is booked', function (string $existing) {
    bookPackage($this->{$existing}, '2027-03-10', 'approved');

    expect(packageFree($this->full, '2027-03-10'))->toBeFalse();
})->with(['day', 'night']);

it('treats touching boundaries as free and one-minute overlaps as taken', function () {
    Booking::factory()->window(Carbon::parse('2027-03-10 07:00'), Carbon::parse('2027-03-10 17:00'))->create();

    expect($this->availability->isAvailable(Carbon::parse('2027-03-10 17:00'), Carbon::parse('2027-03-10 19:00')))->toBeTrue()
        ->and($this->availability->isAvailable(Carbon::parse('2027-03-10 05:00'), Carbon::parse('2027-03-10 07:00')))->toBeTrue()
        ->and($this->availability->isAvailable(Carbon::parse('2027-03-10 16:59'), Carbon::parse('2027-03-10 19:00')))->toBeFalse()
        ->and($this->availability->isAvailable(Carbon::parse('2027-03-10 05:00'), Carbon::parse('2027-03-10 07:01')))->toBeFalse();
});

it('ignores cancelled, rejected and soft-deleted bookings', function () {
    bookPackage($this->day, '2027-03-10', 'cancelled');
    bookPackage($this->day, '2027-03-11', 'rejected');
    bookPackage($this->day, '2027-03-12')->delete();

    expect(packageFree($this->day, '2027-03-10'))->toBeTrue()
        ->and(packageFree($this->day, '2027-03-11'))->toBeTrue()
        ->and(packageFree($this->day, '2027-03-12'))->toBeTrue();
});

it('can ignore the booking being rescheduled', function () {
    $booking = bookPackage($this->day, '2027-03-10');

    expect(packageFree($this->day, '2027-03-10'))->toBeFalse()
        ->and(packageFree($this->day, '2027-03-10', $booking->id))->toBeTrue();
});

it('treats blocked dates like bookings, with the same half-open rule', function () {
    BlockedDate::factory()->create(['starts_at' => Carbon::parse('2027-03-10 00:00'), 'ends_at' => Carbon::parse('2027-03-11 00:00')]);
    BlockedDate::factory()->create(['starts_at' => Carbon::parse('2027-03-15 12:00'), 'ends_at' => Carbon::parse('2027-03-15 14:00')]);

    expect(packageFree($this->day, '2027-03-10'))->toBeFalse()
        ->and(packageFree($this->night, '2027-03-10'))->toBeFalse()
        ->and(packageFree($this->night, '2027-03-09'))->toBeFalse() // ends 05:00 inside the block
        ->and(packageFree($this->day, '2027-03-11'))->toBeTrue()
        ->and(packageFree($this->day, '2027-03-15'))->toBeFalse() // partial block during the day
        ->and(packageFree($this->night, '2027-03-15'))->toBeTrue();
});

it('lists what conflicts with a window', function () {
    $booking = bookPackage($this->day, '2027-03-10');
    $block = BlockedDate::factory()->create(['starts_at' => Carbon::parse('2027-03-10 20:00'), 'ends_at' => Carbon::parse('2027-03-10 22:00')]);

    $conflicts = $this->availability->conflicts(Carbon::parse('2027-03-10 00:00'), Carbon::parse('2027-03-11 00:00'));

    expect($conflicts['bookings']->pluck('id')->all())->toBe([$booking->id])
        ->and($conflicts['blocks']->pluck('id')->all())->toBe([$block->id]);
});

it('builds a per-day availability map for the calendar', function () {
    bookPackage($this->day, '2027-03-10');
    bookPackage($this->full, '2027-03-11');
    BlockedDate::factory()->create(['starts_at' => Carbon::parse('2027-03-12 00:00'), 'ends_at' => Carbon::parse('2027-03-13 00:00')]);

    $map = $this->availability->unavailableDates(Carbon::parse('2027-03-10'), Carbon::parse('2027-03-13'));
    $ids = fn (string $date): array => array_keys(array_filter($map[$date]['packages']));

    expect(array_keys($map))->toBe(['2027-03-10', '2027-03-11', '2027-03-12', '2027-03-13'])
        ->and($ids('2027-03-10'))->toBe([$this->night->id])           // Day taken, Night free, 24H overlaps Day
        ->and($map['2027-03-11']['available'])->toBeFalse()             // 24-Hour booked
        ->and($map['2027-03-12']['available'])->toBeFalse()             // whole-day block
        ->and($ids('2027-03-13'))->toEqualCanonicalizing([$this->day->id, $this->night->id, $this->full->id]);
});

it('builds the map for a single package', function () {
    bookPackage($this->night, '2027-03-10');

    $map = $this->availability->unavailableDates(Carbon::parse('2027-03-10'), Carbon::parse('2027-03-11'), $this->night);

    expect($map['2027-03-10'])->toBe(['available' => false, 'packages' => [$this->night->id => false]])
        ->and($map['2027-03-11']['available'])->toBeTrue();
});

it('excludes inactive packages from the all-packages map', function () {
    $inactive = dayPackage(['code' => 'OLD', 'is_active' => false]);

    $map = $this->availability->unavailableDates(Carbon::parse('2027-03-10'), Carbon::parse('2027-03-10'));

    expect(array_keys($map['2027-03-10']['packages']))->not->toContain($inactive->id);
});

it('checks the lead time and maximum advance settings', function () {
    $now = Carbon::parse('2027-03-10 08:00');

    expect($this->availability->isWithinBookingWindow(Carbon::parse('2027-03-11 07:00'), $now))->toBeFalse() // 23h < 24h lead
        ->and($this->availability->isWithinBookingWindow(Carbon::parse('2027-03-11 08:00'), $now))->toBeTrue()
        ->and($this->availability->isWithinBookingWindow(Carbon::parse('2028-03-09 19:00'), $now))->toBeTrue()  // day 365
        ->and($this->availability->isWithinBookingWindow(Carbon::parse('2028-03-10 07:00'), $now))->toBeFalse(); // day 366
});
