<?php

/*
| M4: BookingService — creation with price snapshot, double-booking defenses (advisory lock,
| re-check, exclusion constraint), status transitions and rescheduling.
*/

use App\Enums\ActivityAction;
use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Exceptions\Booking\GuestCountExceededException;
use App\Exceptions\Booking\InvalidStatusTransitionException;
use App\Exceptions\Booking\PackageUnavailableException;
use App\Exceptions\Booking\SlotUnavailableException;
use App\Models\ActivityLog;
use App\Models\AddOn;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\BookingAddOn;
use App\Models\PricingRule;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingService;
use App\Services\Booking\ReferenceCodeGenerator;
use App\Services\SettingService;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(BookingService::class);
    $this->day = dayPackage();
    $this->night = nightPackage();
    $this->full = fullDayPackage();
    $this->owner = User::factory()->owner()->create();
});

/*
|--------------------------------------------------------------------------
| create()
|--------------------------------------------------------------------------
*/

it('creates a pending booking with a price and add-on snapshot', function () {
    PricingRule::factory()->create(['days_of_week' => [6, 7], 'adjustment_value' => 10]); // weekend +10%
    $videoke = AddOn::factory()->create(['price_cents' => 50_000]);

    $booking = $this->service->create(bookingData($this->night, '2027-01-02', ['add_ons' => [$videoke->id => 2], 'event_type' => 'Birthday']));

    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->reference_code)->toMatch(ReferenceCodeGenerator::pattern())->toStartWith('WP-2701-')
        ->and($booking->starts_at->format('Y-m-d H:i'))->toBe('2027-01-02 19:00')
        ->and($booking->ends_at->format('Y-m-d H:i'))->toBe('2027-01-03 05:00')
        ->and($booking->total_amount_cents)->toBe(990_000 + 100_000)
        ->and($booking->downpayment_required_cents)->toBe(545_000)
        ->and($booking->guest_email)->toBe('juan@example.com')
        ->and(BookingAddOn::query()->where('booking_id', $booking->id)->sole()->only(['add_on_id', 'quantity', 'unit_price_cents']))
        ->toBe(['add_on_id' => $videoke->id, 'quantity' => 2, 'unit_price_cents' => 50_000]);

    // Later price changes never alter the snapshot.
    $videoke->update(['price_cents' => 99_900]);
    $this->night->update(['base_price_cents' => 1]);
    expect($booking->fresh()->total_amount_cents)->toBe(1_090_000)
        ->and(BookingAddOn::query()->where('booking_id', $booking->id)->value('unit_price_cents'))->toBe(50_000);

    $log = ActivityLog::query()->where('action', ActivityAction::BookingCreated->value)->sole();
    expect($log->subject_id)->toBe($booking->id)->and($log->user_id)->toBeNull();
});

it('records the admin who created a walk-in booking', function () {
    $this->service->create(bookingData($this->day, '2027-01-04'), $this->owner);

    expect(ActivityLog::query()->where('action', ActivityAction::BookingCreated->value)->value('user_id'))->toBe($this->owner->id);
});

it('allows a Day and a Night on the same date but not a 24-Hour', function () {
    $this->service->create(bookingData($this->day, '2027-01-04'));
    $this->service->create(bookingData($this->night, '2027-01-04'));

    $this->service->create(bookingData($this->full, '2027-01-04'));
})->throws(SlotUnavailableException::class);

it('refuses a taken slot', function () {
    $this->service->create(bookingData($this->full, '2027-01-04'));

    $this->service->create(bookingData($this->day, '2027-01-04'));
})->throws(SlotUnavailableException::class);

it('refuses a blocked date', function () {
    BlockedDate::factory()->create(['starts_at' => Carbon::parse('2027-01-04 00:00'), 'ends_at' => Carbon::parse('2027-01-05 00:00')]);

    $this->service->create(bookingData($this->day, '2027-01-04'));
})->throws(SlotUnavailableException::class);

it('refuses inactive or missing packages', function (Closure $packageId) {
    $this->service->create(bookingData($this->day, '2027-01-04', ['package_id' => $packageId()]));
})->with([
    'inactive' => fn () => dayPackage(['code' => 'OFF', 'is_active' => false])->id,
    'missing' => fn () => 999_999,
])->throws(PackageUnavailableException::class);

it('refuses too many guests and writes nothing', function () {
    expect(fn () => $this->service->create(bookingData($this->day, '2027-01-04', ['guest_count' => 51])))->toThrow(GuestCountExceededException::class)
        ->and(Booking::query()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Concurrency / double-booking defenses
|--------------------------------------------------------------------------
*/

it('serializes creation with a PostgreSQL advisory lock', function () {
    $config = config('database.connections.pgsql');
    $other = new PDO("pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}", $config['username'], $config['password']);
    $other->query('SELECT pg_advisory_lock('.BookingService::BOOKING_LOCK_KEY.')');

    try {
        DB::statement("SET lock_timeout = '300ms'");

        // Another request holds the booking lock: this one must wait (and here time out) instead of racing.
        expect(fn () => $this->service->create(bookingData($this->day, '2027-01-04')))
            ->toThrow(fn (QueryException $e) => expect($e->getCode())->toBe('55P03'));
        expect(Booking::query()->count())->toBe(0);
    } finally {
        $other->query('SELECT pg_advisory_unlock('.BookingService::BOOKING_LOCK_KEY.')');
        $other = null;
    }

    // Once the other request is done, creation proceeds.
    expect($this->service->create(bookingData($this->day, '2027-01-04'))->exists)->toBeTrue();
});

it('re-checks availability inside the lock (a slot taken after the guest saw it free)', function () {
    $window = app(AvailabilityService::class)->resolveWindow($this->day, Carbon::parse('2027-01-04'));
    expect(app(AvailabilityService::class)->isAvailable($window['starts_at'], $window['ends_at']))->toBeTrue();

    // Another guest books in between page load and submit.
    Booking::factory()->for($this->full)->window(Carbon::parse('2027-01-04 07:00'), Carbon::parse('2027-01-05 05:00'))->create();

    $this->service->create(bookingData($this->day, '2027-01-04'));
})->throws(SlotUnavailableException::class);

it('has a database exclusion constraint against overlapping active bookings', function () {
    Booking::factory()->window(Carbon::parse('2027-01-04 07:00'), Carbon::parse('2027-01-04 17:00'))->create();

    $insert = fn (string $start, string $end, BookingStatus $status = BookingStatus::Pending) => DB::transaction(
        fn () => Booking::factory()->window(Carbon::parse($start), Carbon::parse($end))->create(['status' => $status]),
    );

    expect(fn () => $insert('2027-01-04 16:00', '2027-01-04 20:00'))->toThrow(fn (QueryException $e) => expect($e->getCode())->toBe('23P01'));

    // Touching windows and inactive statuses are allowed.
    expect($insert('2027-01-04 17:00', '2027-01-05 05:00')->exists)->toBeTrue()
        ->and($insert('2027-01-04 08:00', '2027-01-04 09:00', BookingStatus::Cancelled)->exists)->toBeTrue();
});

it('translates an exclusion-constraint violation into SlotUnavailableException', function () {
    Booking::factory()->for($this->day)->window(Carbon::parse('2027-01-04 07:00'), Carbon::parse('2027-01-04 17:00'))->create();

    // Simulate a race the application check missed: availability always says "free".
    app()->instance(AvailabilityService::class, new class (app(SettingService::class)) extends AvailabilityService {
        public function isAvailable(CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): bool
        {
            return true;
        }
    });

    expect(fn () => app(BookingService::class)->create(bookingData($this->full, '2027-01-04')))
        ->toThrow(SlotUnavailableException::class, 'just booked')
        ->and(Booking::query()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| transition()
|--------------------------------------------------------------------------
*/

/**
 * Booking in the given status whose stay has already ended (so "completed" is reachable).
 */
function pastBooking(BookingStatus $status): Booking
{
    return Booking::factory()->window(Carbon::parse('2026-01-10 07:00'), Carbon::parse('2026-01-10 17:00'))->create(['status' => $status]);
}

it('enforces the transition map for every status pair', function (BookingStatus $from, BookingStatus $to) {
    Event::fake([BookingStatusChanged::class]);
    $booking = pastBooking($from);
    $allowed = in_array($to, BookingService::TRANSITIONS[$from->value], true);

    if ($allowed) {
        $this->service->transition($booking, $to, $this->owner, 'Reason given');
        expect($booking->fresh()->status)->toBe($to);
        Event::assertDispatched(BookingStatusChanged::class, fn (BookingStatusChanged $e): bool => $e->from === $from && $e->to === $to && $e->actor?->is($this->owner));
    } else {
        expect(fn () => $this->service->transition($booking, $to, $this->owner, 'Reason given'))->toThrow(InvalidStatusTransitionException::class)
            ->and($booking->fresh()->status)->toBe($from);
        Event::assertNotDispatched(BookingStatusChanged::class);
    }
})->with(function () {
    foreach (BookingStatus::cases() as $from) {
        foreach (BookingStatus::cases() as $to) {
            yield "{$from->value} → {$to->value}" => [$from, $to];
        }
    }
});

it('stamps the approver and logs the change', function () {
    $booking = $this->service->create(bookingData($this->day, '2027-01-04'));

    $this->service->transition($booking, BookingStatus::Approved, $this->owner);

    $booking->refresh();
    expect($booking->approved_by)->toBe($this->owner->id)
        ->and($booking->approved_at)->not->toBeNull();

    $log = ActivityLog::query()->where('action', ActivityAction::BookingStatusChanged->value)->sole();
    expect($log->properties)->toEqualCanonicalizing(['from' => 'pending', 'to' => 'approved'])->and($log->user_id)->toBe($this->owner->id);
});

it('requires a reason to reject and stores it', function () {
    $booking = $this->service->create(bookingData($this->day, '2027-01-04'));

    expect(fn () => $this->service->transition($booking, BookingStatus::Rejected, $this->owner, '  '))->toThrow(InvalidStatusTransitionException::class, 'reason');

    $this->service->transition($booking, BookingStatus::Rejected, $this->owner, 'Proof unreadable');
    expect($booking->fresh()->rejection_reason)->toBe('Proof unreadable');
});

it('refuses to complete a stay that has not ended', function () {
    $booking = $this->service->create(bookingData($this->day, '2027-01-04'));
    $this->service->transition($booking, BookingStatus::Approved, $this->owner);

    $this->service->transition($booking, BookingStatus::Completed, $this->owner);
})->throws(InvalidStatusTransitionException::class, 'after the stay');

it('frees the slot when a booking is cancelled', function () {
    $booking = $this->service->create(bookingData($this->day, '2027-01-04'));
    $this->service->transition($booking, BookingStatus::Cancelled, $this->owner);

    expect($this->service->create(bookingData($this->full, '2027-01-04'))->exists)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| reschedule()
|--------------------------------------------------------------------------
*/

it('reschedules to a free date, keeping the price snapshot', function () {
    PricingRule::factory()->create(['days_of_week' => [6, 7], 'adjustment_value' => 10]);
    $booking = $this->service->create(bookingData($this->day, '2027-01-04')); // Monday: ₱7,000

    $this->service->reschedule($booking, Carbon::parse('2027-01-09'), $this->owner); // Saturday

    expect($booking->starts_at->format('Y-m-d H:i'))->toBe('2027-01-09 07:00')
        ->and($booking->total_amount_cents)->toBe(700_000);

    $log = ActivityLog::query()->where('action', ActivityAction::BookingRescheduled->value)->sole();
    expect($log->properties['repriced'])->toBeFalse()->and($log->properties['from']['starts_at'])->toBe('2027-01-04 07:00:00');
});

it('reprices on request using the current add-ons and rules', function () {
    PricingRule::factory()->create(['days_of_week' => [6, 7], 'adjustment_value' => 10]);
    $addOn = AddOn::factory()->create(['price_cents' => 10_000]);
    $booking = $this->service->create(bookingData($this->day, '2027-01-04', ['add_ons' => [$addOn->id => 1]]));
    $addOn->update(['price_cents' => 20_000]);

    $this->service->reschedule($booking, Carbon::parse('2027-01-09'), $this->owner, $this->night, reprice: true);

    expect($booking->package_id)->toBe($this->night->id)
        ->and($booking->ends_at->format('Y-m-d H:i'))->toBe('2027-01-10 05:00')
        ->and($booking->total_amount_cents)->toBe(990_000 + 20_000)
        ->and(BookingAddOn::query()->where('booking_id', $booking->id)->value('unit_price_cents'))->toBe(20_000);
});

it('can move a booking to an overlapping time of its own (ignores itself)', function () {
    $booking = $this->service->create(bookingData($this->day, '2027-01-04'));

    $this->service->reschedule($booking, Carbon::parse('2027-01-04'), $this->owner, $this->full);

    expect($booking->ends_at->format('Y-m-d H:i'))->toBe('2027-01-05 05:00');
});

it('refuses to reschedule into another booking or a blocked date', function (Closure $occupy) {
    $booking = $this->service->create(bookingData($this->day, '2027-01-04'));
    $occupy();

    expect(fn () => $this->service->reschedule($booking, Carbon::parse('2027-01-08'), $this->owner))->toThrow(SlotUnavailableException::class)
        ->and($booking->fresh()->starts_at->format('Y-m-d'))->toBe('2027-01-04');
})->with([
    'booking' => fn () => app(BookingService::class)->create(bookingData(App\Models\Package::query()->where('code', '24H')->sole(), '2027-01-08')),
    'blocked date' => fn () => BlockedDate::factory()->create(['starts_at' => Carbon::parse('2027-01-08 00:00'), 'ends_at' => Carbon::parse('2027-01-09 00:00')]),
]);

it('refuses to reschedule closed bookings', function () {
    $booking = pastBooking(BookingStatus::Completed);

    $this->service->reschedule($booking, Carbon::parse('2027-02-01'), $this->owner);
})->throws(InvalidStatusTransitionException::class, 'Only pending or approved');

it('checks the guest count against a new package', function () {
    $small = dayPackage(['code' => 'SMALL', 'max_pax' => 10]);
    $booking = $this->service->create(bookingData($this->day, '2027-01-04', ['guest_count' => 30]));

    $this->service->reschedule($booking, Carbon::parse('2027-01-05'), $this->owner, $small);
})->throws(GuestCountExceededException::class);
