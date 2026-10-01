<?php

/*
| M4: bookings:expire-stale — unpaid pending bookings older than booking.pending_hold_hours are cancelled.
*/

use App\Enums\ActivityAction;
use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Booking\BookingService;
use App\Services\SettingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2027-01-10 12:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Pending booking created $hours ago.
 */
function pendingCreatedHoursAgo(int $hours): Booking
{
    return Booking::factory()->create(['created_at' => now()->subHours($hours)]);
}

it('cancels stale unpaid pending bookings and keeps the rest', function () {
    Event::fake([BookingStatusChanged::class]);

    $stale = pendingCreatedHoursAgo(25);
    $exactlyAtCutoff = pendingCreatedHoursAgo(24);
    $fresh = pendingCreatedHoursAgo(23);
    $withProof = pendingCreatedHoursAgo(48);
    Payment::factory()->for($withProof)->create(['proof_path' => 'payment-proofs/x.jpg']);
    $paymentWithoutProof = pendingCreatedHoursAgo(30);
    Payment::factory()->for($paymentWithoutProof)->create(['proof_path' => null]);
    $approved = Booking::factory()->approved()->create(['created_at' => now()->subDays(5)]);

    expect(app(BookingService::class)->expireStale())->toBe(3);

    expect($stale->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($exactlyAtCutoff->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($paymentWithoutProof->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($fresh->fresh()->status)->toBe(BookingStatus::Pending)
        ->and($withProof->fresh()->status)->toBe(BookingStatus::Pending)
        ->and($approved->fresh()->status)->toBe(BookingStatus::Approved);

    $log = ActivityLog::query()->where('action', ActivityAction::BookingExpired->value)->where('subject_id', $stale->id)->sole();
    expect($log->user_id)->toBeNull()
        ->and($log->properties)->toMatchArray(['from' => 'pending', 'to' => 'cancelled', 'reason' => 'Payment proof not received within 24 hours.']);
    Event::assertDispatchedTimes(BookingStatusChanged::class, 3);
});

it('follows the pending hold setting', function () {
    Setting::query()->create(['key' => 'booking.pending_hold_hours', 'value' => '48', 'group' => 'booking']);
    app(SettingService::class)->flush();
    $booking = pendingCreatedHoursAgo(30);

    expect(app(BookingService::class)->expireStale())->toBe(0)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Pending);
});

it('runs from the command, with a dry-run mode', function () {
    $booking = pendingCreatedHoursAgo(30);

    $this->artisan('bookings:expire-stale --dry-run')->expectsOutput('1 pending booking(s) would expire.')->assertSuccessful();
    expect($booking->fresh()->status)->toBe(BookingStatus::Pending);

    $this->artisan('bookings:expire-stale')->expectsOutput('1 pending booking(s) expired.')->assertSuccessful();
    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
});

it('is scheduled every 15 minutes', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($e): bool => str_contains($e->command ?? '', 'bookings:expire-stale'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/15 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});
