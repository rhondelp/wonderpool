<?php

/*
| M8: lifecycle emails (D-037) — right notification per event, toggles, recipients, after-commit,
| queued, content (reference + dates, nothing sensitive), failure logging.
*/

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\NotificationType;
use App\Enums\SettingGroup;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingNotification;
use App\Notifications\BookingReceived;
use App\Notifications\BookingRejected;
use App\Notifications\BookingReminder;
use App\Notifications\PaymentProofReceived;
use App\Services\Booking\BookingService;
use App\Services\Booking\PaymentProofService;
use App\Services\SettingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Notification::fake();
    $this->owner = User::factory()->owner()->create();
    User::factory()->owner()->inactive()->create(); // disabled owners get nothing
    User::factory()->create(); // staff get no owner alerts
    $this->day = dayPackage();
    $this->engine = app(BookingService::class);
});

/**
 * Turns one notification type off.
 */
function switchOff(NotificationType $type): void
{
    app(SettingService::class)->update(SettingGroup::Notifications, [$type->settingKey() => '0']);
}

/**
 * A pending guest booking with an email address, created without firing notifications.
 */
function pendingGuestBooking(array $attributes = []): Booking
{
    return Booking::factory()->for(App\Models\Package::query()->firstOrFail())
        ->window(Carbon::parse('2027-03-06 07:00'), Carbon::parse('2027-03-06 17:00'))
        ->create($attributes + ['reference_code' => 'WP-2703-MAIL', 'guest_email' => 'guest@example.com', 'guest_name' => 'Maria Santos']);
}

it('emails the guest and active owners when a guest books', function () {
    $booking = $this->engine->create(bookingData($this->day, '2027-03-06', ['guest_email' => 'Guest@Example.com']));

    Notification::assertSentOnDemand(BookingReceived::class, fn (BookingReceived $n, array $channels, AnonymousNotifiable $to) => ! $n->forOwner
        && $n->booking->is($booking) && $to->routes['mail'] === 'guest@example.com' && $channels === ['mail']);
    Notification::assertSentTo($this->owner, BookingReceived::class, fn (BookingReceived $n) => $n->forOwner);
    Notification::assertCount(2);
});

it('sends nothing for walk-ins until they are approved', function () {
    $this->engine->create(bookingData($this->day, '2027-03-06', ['source' => BookingSource::Admin, 'guest_email' => 'walkin@example.com']), $this->owner);

    Notification::assertNothingSent();
});

it('respects the booking-received switches', function () {
    switchOff(NotificationType::BookingReceivedGuest);
    $this->engine->create(bookingData($this->day, '2027-03-06'));

    Notification::assertNotSentTo(new AnonymousNotifiable(), BookingReceived::class);
    Notification::assertSentTo($this->owner, BookingReceived::class);
    Notification::assertCount(1);
});

it('emails the guest on approval, rejection with reason and cancellation', function (BookingStatus $to, string $class, ?string $reason) {
    $booking = pendingGuestBooking();
    $this->engine->transition($booking, $to, $this->owner, $reason);

    Notification::assertSentOnDemand($class, fn ($n) => $n->booking->is($booking) && ($reason === null || $n->reason === $reason));
    Notification::assertCount(1);
})->with([
    'approved' => [BookingStatus::Approved, BookingApproved::class, null],
    'rejected' => [BookingStatus::Rejected, BookingRejected::class, 'Fully booked for a private event'],
    'cancelled' => [BookingStatus::Cancelled, BookingCancelled::class, 'Guest asked to cancel'],
]);

it('does not send a status email that is switched off', function (BookingStatus $to, NotificationType $type) {
    switchOff($type);
    $this->engine->transition(pendingGuestBooking(), $to, $this->owner, 'Reason');

    Notification::assertNothingSent();
})->with([
    'approved' => [BookingStatus::Approved, NotificationType::BookingApproved],
    'rejected' => [BookingStatus::Rejected, NotificationType::BookingRejected],
    'cancelled' => [BookingStatus::Cancelled, NotificationType::BookingCancelled],
]);

it('tells the guest when an unpaid request expires', function () {
    $booking = pendingGuestBooking(['created_at' => now()->subDays(3)]);

    $this->engine->expireStale();

    Notification::assertSentOnDemand(BookingCancelled::class, fn (BookingCancelled $n) => $n->booking->is($booking) && $n->expired && str_contains((string) $n->reason, 'Payment proof not received'));
});

it('sends nothing on completion or when the guest has no email', function () {
    $this->engine->transition(pendingGuestBooking(['guest_email' => null]), BookingStatus::Approved, $this->owner);
    $done = Booking::factory()->for($this->day)->approved()->window(Carbon::parse('2026-01-01 07:00'), Carbon::parse('2026-01-01 17:00'))->create(['guest_email' => 'x@example.com']);
    $this->engine->transition($done, BookingStatus::Completed, $this->owner);

    Notification::assertNothingSent();
});

it('emails owners when a payment proof is uploaded, and respects the switch', function () {
    Storage::fake('local');
    $booking = pendingGuestBooking();

    app(PaymentProofService::class)->store($booking, UploadedFile::fake()->image('receipt.jpg', 400, 600));
    Notification::assertSentTo($this->owner, PaymentProofReceived::class, fn ($n) => $n->booking->is($booking));
    Notification::assertCount(1);

    switchOff(NotificationType::PaymentProofReceived);
    app(PaymentProofService::class)->store($booking, UploadedFile::fake()->image('again.jpg', 400, 600));
    Notification::assertCount(1);
});

it('sends nothing when the surrounding transaction rolls back', function () {
    $booking = pendingGuestBooking();

    try {
        DB::transaction(function () use ($booking): void {
            $this->engine->transition($booking, BookingStatus::Approved, $this->owner);
            throw new RuntimeException('approval aborted');
        });
    } catch (RuntimeException) {
    }

    Notification::assertNothingSent();
});

it('queues every booking notification after commit', function (string $class) {
    $notification = new $class(pendingGuestBooking());

    expect($notification)->toBeInstanceOf(ShouldQueue::class)->toBeInstanceOf(BookingNotification::class)
        ->and($notification->afterCommit)->toBeTrue();
})->with([BookingReceived::class, BookingApproved::class, BookingRejected::class, BookingCancelled::class, PaymentProofReceived::class, BookingReminder::class]);

it('renders branded emails with the reference code and dates but nothing sensitive', function (string $class, array $args, string $expect) {
    app(SettingService::class)->update(SettingGroup::General, ['general.logo_url' => 'https://cdn.example.com/logo.png']);
    $booking = pendingGuestBooking(['guest_phone' => '+639171234567', 'rejection_reason' => null]);
    Storage::fake('local');
    $booking->payments()->create(['type' => 'downpayment', 'amount_cents' => 100, 'status' => 'pending', 'proof_path' => 'payment-proofs/1/secret-file.jpg']);

    $html = (string) (new $class($booking, ...$args))->toMail(Notification::route('mail', 'guest@example.com'))->render();

    expect($html)->toContain('WP-2703-MAIL')
        ->toContain('Sat, Mar 6, 2027, 7:00 AM – 5:00 PM')
        ->toContain($expect)
        ->toContain('https://cdn.example.com/logo.png')
        ->toContain('info@example.com') // resort contact (settings default) in the footer
        ->not->toContain('+639171234567')
        ->not->toContain('secret-file')
        ->not->toContain('/proof');
})->with([
    'received (guest)' => [BookingReceived::class, [], 'Track my booking'],
    'received (owner)' => [BookingReceived::class, [true], 'New booking request'],
    'approved' => [BookingApproved::class, [], 'confirmed'],
    'rejected' => [BookingRejected::class, ['No vacancy that weekend'], 'No vacancy that weekend'],
    'cancelled' => [BookingCancelled::class, ['Payment proof not received within 24 hours.', true], 'has expired'],
    'proof received' => [PaymentProofReceived::class, [], 'Payment proof to review'],
    'reminder' => [BookingReminder::class, [], 'See you soon'],
]);

it('puts the reference in the subject line', function () {
    $mail = (new BookingApproved(pendingGuestBooking()))->toMail(new AnonymousNotifiable());

    expect($mail->subject)->toBe('Booking confirmed: WP-2703-MAIL · Wonderpool Garden Resort');
});

it('logs a final send failure without the recipient address', function () {
    $booking = pendingGuestBooking();

    (new BookingApproved($booking))->failed(new RuntimeException('Expected response code 250 but got 550 for guest@example.com'));

    $log = ActivityLog::query()->where('action', 'mail.failed')->sole();
    expect($log->user_id)->toBeNull()
        ->and($log->subject_id)->toBe($booking->id)
        ->and($log->properties['notification'])->toBe('booking_approved')
        ->and($log->properties['error'])->toContain('[email]')->not->toContain('guest@example.com');
});
