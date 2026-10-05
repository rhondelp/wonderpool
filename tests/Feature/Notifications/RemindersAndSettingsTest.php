<?php

/*
| M8: bookings:send-reminders idempotency (D-038), schedule, Settings → Notifications toggles,
| test email, channel resolver.
*/

use App\Enums\NotificationType;
use App\Enums\SettingGroup;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingReminder;
use App\Notifications\Channels\NotificationChannelResolver;
use App\Notifications\TestEmail;
use App\Services\Booking\BookingService;
use App\Services\SettingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow('2027-03-05 09:00');
    $this->day = dayPackage();
    $this->tomorrow = Booking::factory()->for($this->day)->approved()
        ->window(Carbon::parse('2027-03-06 07:00'), Carbon::parse('2027-03-06 17:00'))
        ->create(['guest_email' => 'guest@example.com', 'reference_code' => 'WP-2703-TMRW']);
});

afterEach(fn () => Carbon::setTestNow());

it('reminds approved stays starting tomorrow exactly once', function () {
    Booking::factory()->for($this->day)->window(Carbon::parse('2027-03-06 19:00'), Carbon::parse('2027-03-06 23:00'))->create(['guest_email' => 'pending@example.com']); // pending
    Booking::factory()->for($this->day)->approved()->window(Carbon::parse('2027-03-08 07:00'), Carbon::parse('2027-03-08 17:00'))->create(['guest_email' => 'later@example.com']); // too early
    Booking::factory()->for($this->day)->approved()->window(Carbon::parse('2027-03-05 07:00'), Carbon::parse('2027-03-05 17:00'))->create(['guest_email' => 'started@example.com']); // already started
    Booking::factory()->for($this->day)->approved()->window(Carbon::parse('2027-03-06 01:00'), Carbon::parse('2027-03-06 05:00'))->create(['guest_email' => null]); // no email

    $this->artisan('bookings:send-reminders')->expectsOutput('1 reminder(s) queued.')->assertSuccessful();
    $this->artisan('bookings:send-reminders')->expectsOutput('0 reminder(s) queued.')->assertSuccessful();

    Notification::assertSentOnDemandTimes(BookingReminder::class, 1);
    Notification::assertSentOnDemand(BookingReminder::class, fn ($n, $c, AnonymousNotifiable $to) => $n->booking->is($this->tomorrow) && $to->routes['mail'] === 'guest@example.com');
    expect($this->tomorrow->fresh()->reminded_at?->toDateTimeString())->toBe('2027-03-05 09:00:00');
});

it('supports a dry run that claims nothing', function () {
    $this->artisan('bookings:send-reminders --dry-run')->expectsOutput('1 reminder(s) due.');

    Notification::assertNothingSent();
    expect($this->tomorrow->fresh()->reminded_at)->toBeNull();
});

it('does not claim bookings while reminders are switched off', function () {
    app(SettingService::class)->update(SettingGroup::Notifications, [NotificationType::BookingReminder->settingKey() => '0']);

    $this->artisan('bookings:send-reminders')->expectsOutput('0 reminder(s) queued.');

    Notification::assertNothingSent();
    expect($this->tomorrow->fresh()->reminded_at)->toBeNull();
});

it('honours the days-before setting', function () {
    app(SettingService::class)->update(SettingGroup::Notifications, ['notifications.reminder_days_before' => '3']);
    Booking::factory()->for($this->day)->approved()->window(Carbon::parse('2027-03-08 07:00'), Carbon::parse('2027-03-08 17:00'))->create(['guest_email' => 'later@example.com']);

    $this->artisan('bookings:send-reminders')->expectsOutput('2 reminder(s) queued.');
});

it('reminds again after a reschedule', function () {
    $this->artisan('bookings:send-reminders');
    app(BookingService::class)->reschedule($this->tomorrow->fresh(), Carbon::parse('2027-03-20'), User::factory()->owner()->create());

    expect($this->tomorrow->fresh()->reminded_at)->toBeNull();
});

it('schedules reminders daily at 09:00 Manila time', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'bookings:send-reminders'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 9 * * *')
        ->and($event->timezone)->toBe('Asia/Manila');
});

it('shows notification switches and saves an unticked box as off', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->get('/admin/settings/notifications')->assertOk()
        ->assertSee('Booking approved (to guest)')
        ->assertSee('Send test email');

    $payload = ['notifications' => ['reminder_days_before' => '1']];
    foreach (NotificationType::cases() as $type) {
        $payload['notifications'][$type->value] = $type === NotificationType::BookingApproved ? '0' : '1';
    }
    $this->actingAs($owner)->put('/admin/settings/notifications', $payload)->assertRedirect()->assertSessionHasNoErrors();

    expect(app(SettingService::class)->bool('notifications.booking_approved'))->toBeFalse()
        ->and(app(SettingService::class)->bool('notifications.booking_rejected'))->toBeTrue();
});

it('queues a test email to the signed-in owner and logs it', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->post('/admin/settings/notifications/test-email')->assertRedirect()->assertSessionHas('success');

    Notification::assertSentTo($owner, TestEmail::class);
    expect(ActivityLog::query()->where('action', 'mail.test_queued')->sole()->user_id)->toBe($owner->id)
        ->and((new TestEmail())->toMail($owner)->render()->toHtml())->toContain('It works!');
});

it('keeps the test email owner only', function () {
    $this->actingAs(User::factory()->create())->post('/admin/settings/notifications/test-email')->assertForbidden();

    Notification::assertNothingSent();
});

it('resolves channels from config and drops ones the recipient cannot receive', function () {
    $resolver = app(NotificationChannelResolver::class);

    expect($resolver->channels(NotificationType::BookingApproved, Notification::route('mail', 'a@example.com')))->toBe(['mail'])
        ->and($resolver->channels(NotificationType::BookingApproved, new AnonymousNotifiable()))->toBe([]);

    config(['wonderpool.notifications.channels.booking_approved' => ['mail', 'sms']]);
    expect($resolver->channels(NotificationType::BookingApproved, Notification::route('mail', 'a@example.com')->route('sms', '+639171234567')))->toBe(['mail', 'sms'])
        ->and($resolver->channels(NotificationType::BookingRejected, Notification::route('mail', 'a@example.com')->route('sms', '+639171234567')))->toBe(['mail']);
});
