<?php

/*
| M6: approve / reject / cancel / complete / reschedule / notes from the admin detail page.
| Staff may do all of these (D-029). "Now" = 2027-01-01 09:00.
*/

use App\Enums\ActivityAction;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Events\BookingStatusChanged;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\User;
use App\Services\Booking\BookingService;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 09:00'));
    $this->seed(SettingSeeder::class);
    $this->staff = User::factory()->create();
    $this->actingAs($this->staff);
    $this->day = dayPackage();
    $this->booking = app(BookingService::class)->create(bookingData($this->day, '2027-01-04')); // ₱7,000, downpayment ₱3,500
});

afterEach(fn () => Carbon::setTestNow());

it('shows the detail page with snapshot, payments, timeline and allowed actions', function () {
    Payment::factory()->for($this->booking)->create(['amount_cents' => 350_000, 'proof_path' => 'payment-proofs/p.jpg']);

    $this->get(route('admin.bookings.show', $this->booking))->assertOk()
        ->assertSee($this->booking->reference_code)
        ->assertSee('Juan Dela Cruz')->assertSee('0917 123 4567')
        ->assertSee('₱7,000.00')->assertSee('Balance due')
        ->assertSee('View proof')->assertSee(route('admin.payments.proof', Payment::query()->sole()), false)
        ->assertSee('Created booking')
        ->assertSee('Approve')->assertSee('Reject')->assertSee('Reschedule')
        ->assertDontSee('Mark completed');
});

it('approves after auto-verifying a pending proof that covers the downpayment', function () {
    Event::fake([BookingStatusChanged::class]);
    $payment = Payment::factory()->for($this->booking)->create(['amount_cents' => 350_000, 'proof_path' => 'payment-proofs/p.jpg']);

    $this->post(route('admin.bookings.approve', $this->booking))
        ->assertRedirect(route('admin.bookings.show', $this->booking))->assertSessionHas('success', 'Booking approved.');

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Approved)
        ->and($this->booking->fresh()->approved_by)->toBe($this->staff->id)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Verified)
        ->and($payment->fresh()->verified_by)->toBe($this->staff->id)
        ->and(ActivityLog::query()->where('action', ActivityAction::PaymentVerified->value)->exists())->toBeTrue();
    Event::assertDispatched(BookingStatusChanged::class, fn (BookingStatusChanged $e): bool => $e->to === BookingStatus::Approved);
});

it('refuses approval when the downpayment is not covered and rolls back auto-verification', function () {
    $payment = Payment::factory()->for($this->booking)->create(['amount_cents' => 100_000, 'proof_path' => 'payment-proofs/p.jpg']);

    $this->post(route('admin.bookings.approve', $this->booking))
        ->assertSessionHas('error', fn (string $m): bool => str_contains($m, '₱2,500.00 still due'));

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Pending)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(ActivityLog::query()->where('action', ActivityAction::PaymentVerified->value)->exists())->toBeFalse();
});

it('approves with a recorded payment and no proof', function () {
    $this->post(route('admin.bookings.payments.store', $this->booking), ['type' => 'downpayment', 'amount' => '3500', 'reference_no' => 'CASH-1']);

    $this->post(route('admin.bookings.approve', $this->booking))->assertSessionHas('success');
    expect($this->booking->fresh()->status)->toBe(BookingStatus::Approved);
});

it('rejects with a required reason shown to the guest', function () {
    $this->post(route('admin.bookings.reject', $this->booking), ['reason' => ''])->assertSessionHasErrors(['reason' => 'Please give the guest a reason for the rejection.']);

    $this->post(route('admin.bookings.reject', $this->booking), ['reason' => 'Proof unreadable'])->assertSessionHas('success');
    expect($this->booking->fresh()->status)->toBe(BookingStatus::Rejected)
        ->and($this->booking->fresh()->rejection_reason)->toBe('Proof unreadable');
});

it('cancels with an optional reason and frees the date', function () {
    $this->post(route('admin.bookings.cancel', $this->booking), ['reason' => 'Guest called to cancel'])->assertSessionHas('success');

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($this->booking->fresh()->cancellation_reason)->toBe('Guest called to cancel')
        ->and(app(BookingService::class)->create(bookingData(fullDayPackage(), '2027-01-04'))->exists)->toBeTrue();
});

it('refuses invalid transitions with a friendly message', function () {
    $this->post(route('admin.bookings.complete', $this->booking))->assertSessionHas('error', 'A Pending booking cannot be changed to Completed.');

    $this->booking->update(['status' => BookingStatus::Rejected]);
    $this->post(route('admin.bookings.approve', $this->booking))->assertSessionHas('error', 'A Rejected booking cannot be approved.');
    $this->post(route('admin.bookings.cancel', $this->booking))->assertSessionHas('error');
});

it('completes an approved booking only after the stay has ended', function () {
    $this->post(route('admin.bookings.payments.store', $this->booking), ['type' => 'full', 'amount' => '7000']);
    $this->post(route('admin.bookings.approve', $this->booking));

    $this->post(route('admin.bookings.complete', $this->booking))->assertSessionHas('error', 'A booking can only be completed after the stay has ended.');

    Carbon::setTestNow(Carbon::parse('2027-01-04 18:00'));
    $this->post(route('admin.bookings.complete', $this->booking))->assertSessionHas('success');
    expect($this->booking->fresh()->status)->toBe(BookingStatus::Completed);
    $this->get(route('admin.bookings.show', $this->booking))->assertDontSee('Reschedule');
});

it('reschedules to a free date and keeps the price unless repriced', function () {
    $night = nightPackage();

    $this->post(route('admin.bookings.reschedule', $this->booking), ['package_id' => $night->id, 'date' => '2027-01-02', 'reprice' => '0'])
        ->assertSessionHas('success', 'Booking rescheduled.');

    $fresh = $this->booking->fresh();
    expect($fresh->package_id)->toBe($night->id)
        ->and($fresh->starts_at->format('Y-m-d H:i'))->toBe('2027-01-02 19:00') // inside the guest lead time: allowed for admins
        ->and($fresh->total_amount_cents)->toBe(700_000);

    $this->post(route('admin.bookings.reschedule', $this->booking), ['package_id' => $night->id, 'date' => '2027-01-05', 'reprice' => '1']);
    expect($this->booking->fresh()->total_amount_cents)->toBe(900_000);
});

it('shows a reschedule conflict without moving the booking', function () {
    app(BookingService::class)->create(bookingData(fullDayPackage(), '2027-01-08'));

    $this->post(route('admin.bookings.reschedule', $this->booking), ['package_id' => $this->day->id, 'date' => '2027-01-08'])
        ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'already booked'));

    expect($this->booking->fresh()->starts_at->format('Y-m-d'))->toBe('2027-01-04');
});

it('serves an admin calendar and quote that ignore the booking itself and the lead time', function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 18:00')); // today's Day window has ended
    $calendar = $this->getJson(route('admin.bookings.calendar', ['package_id' => $this->day->id, 'month' => '2027-01', 'booking' => $this->booking->id]))->assertOk()->json('days');
    expect($calendar['2027-01-04'])->toBe('available')
        ->and($calendar['2027-01-02'])->toBe('available')   // inside guest lead time, fine for admins
        ->and($calendar['2027-01-01'])->toBe('closed');     // already ended

    expect($this->getJson(route('admin.bookings.calendar', ['package_id' => $this->day->id, 'month' => '2027-01']))->json('days.2027-01-04'))->toBe('booked');

    $this->postJson(route('admin.bookings.quote'), ['package_id' => $this->day->id, 'date' => '2027-01-04', 'guest_count' => 10, 'booking' => $this->booking->id])
        ->assertOk()->assertJson(['available' => true]);
    $this->postJson(route('admin.bookings.quote'), ['package_id' => $this->day->id, 'date' => '2027-01-04', 'guest_count' => 10])
        ->assertJson(['available' => false]);
});

it('saves internal notes and logs it, never showing them to guests', function () {
    $this->patch(route('admin.bookings.notes', $this->booking), ['admin_notes' => 'VIP: owner\'s cousin'])->assertSessionHas('success');

    expect($this->booking->fresh()->admin_notes)->toBe('VIP: owner\'s cousin')
        ->and(ActivityLog::query()->where('action', ActivityAction::BookingNotesUpdated->value)->exists())->toBeTrue();

    session([\App\Services\Booking\GuestBookingService::SESSION_KEY => [$this->booking->id]]);
    $this->get(route('track.show', $this->booking))->assertDontSee('VIP');
});
