<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingReceived;
use App\Notifications\BookingRejected;
use App\Notifications\BookingReminder;
use App\Notifications\PaymentProofReceived;
use App\Notifications\TestEmail;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

/**
 * Who gets which email, and whether it is switched on (M8, D-037). Called by
 * Listeners\SendBookingNotifications and the bookings:send-reminders command; never by controllers
 * for booking events. Every send is queued (the notifications implement ShouldQueue).
 */
class NotificationService
{
    /**
     * @param  SettingService  $settings  On/off switches and reminder timing
     * @param  ActivityLogger  $logger  Test email audit entry
     */
    public function __construct(
        private readonly SettingService $settings,
        private readonly ActivityLogger $logger,
    ) {
    }

    /**
     * Whether a notification type is switched on in Settings → Notifications.
     */
    public function enabled(NotificationType $type): bool
    {
        return $this->settings->bool($type->settingKey());
    }

    /**
     * New booking: guest confirmation + owner alert. Walk-ins (source admin) send neither; the
     * guest hears from us when the booking is approved.
     */
    public function bookingCreated(Booking $booking): void
    {
        if ($booking->source !== BookingSource::Guest) {
            return;
        }

        $this->toGuest($booking, NotificationType::BookingReceivedGuest, new BookingReceived($booking));
        $this->toOwners(NotificationType::BookingReceivedOwner, new BookingReceived($booking, forOwner: true));
    }

    /**
     * Status change: approved / rejected (with reason) / cancelled or expired (with reason) → guest.
     * Completed sends nothing.
     *
     * @param  bool  $bySystem  True when no admin made the change (expiry)
     */
    public function statusChanged(Booking $booking, BookingStatus $to, ?string $reason, bool $bySystem = false): void
    {
        match ($to) {
            BookingStatus::Approved => $this->toGuest($booking, NotificationType::BookingApproved, new BookingApproved($booking)),
            BookingStatus::Rejected => $this->toGuest($booking, NotificationType::BookingRejected, new BookingRejected($booking, $reason)),
            BookingStatus::Cancelled => $this->toGuest($booking, NotificationType::BookingCancelled, new BookingCancelled($booking, $reason, $bySystem)),
            default => null,
        };
    }

    /**
     * Payment proof uploaded → owners.
     */
    public function proofUploaded(Booking $booking): void
    {
        $this->toOwners(NotificationType::PaymentProofReceived, new PaymentProofReceived($booking));
    }

    /**
     * Queues reminders for approved stays that start between now and the end of the target day
     * (today + notifications.reminder_days_before) and were not reminded yet. Each booking is
     * claimed with a conditional UPDATE of reminded_at first, so overlapping or repeated runs
     * never send twice (D-038). Nothing is claimed while the reminder is switched off.
     *
     * @param  CarbonInterface|null  $now  Reference time (default: now)
     * @param  bool  $dryRun  Count only
     * @return int Reminders queued (or that would be)
     */
    public function sendReminders(?CarbonInterface $now = null, bool $dryRun = false): int
    {
        if (! $this->enabled(NotificationType::BookingReminder)) {
            return 0;
        }

        $now = CarbonImmutable::instance($now ?? now());
        $days = max(1, $this->settings->int('notifications.reminder_days_before', 1));
        $until = $now->startOfDay()->addDays($days + 1);

        $due = Booking::query()
            ->status(BookingStatus::Approved)
            ->whereNull('reminded_at')
            ->whereNotNull('guest_email')
            ->where('guest_email', '<>', '')
            ->where('starts_at', '>', $now)
            ->where('starts_at', '<', $until)
            ->orderBy('starts_at')
            ->get();

        if ($dryRun) {
            return $due->count();
        }

        $sent = 0;
        foreach ($due as $booking) {
            $claimed = Booking::query()->whereKey($booking->id)->whereNull('reminded_at')->toBase()->update(['reminded_at' => $now]);

            if ($claimed === 1) {
                Notification::route('mail', $booking->guest_email)->notify(new BookingReminder($booking));
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Queues the test email to the given admin and records it.
     */
    public function sendTest(User $user): void
    {
        $user->notify(new TestEmail());
        $this->logger->log(ActivityAction::MailTestQueued, null, [], $user);
    }

    /**
     * Active owners who receive admin alerts.
     *
     * @return Collection<int, User>
     */
    public function owners(): Collection
    {
        return User::query()->active()->where('role', UserRole::Owner)->orderBy('id')->get();
    }

    /**
     * Guest email address as an on-demand recipient, or null when the booking has none.
     */
    public function guest(Booking $booking): ?AnonymousNotifiable
    {
        $email = trim((string) $booking->guest_email);

        return $email === '' ? null : Notification::route('mail', $email);
    }

    /**
     * Sends to the guest when the type is on and the booking has an email address.
     */
    private function toGuest(Booking $booking, NotificationType $type, object $notification): void
    {
        $guest = $this->guest($booking);

        if ($guest !== null && $this->enabled($type)) {
            $guest->notify($notification);
        }
    }

    /**
     * Sends to every active owner when the type is on.
     */
    private function toOwners(NotificationType $type, object $notification): void
    {
        if (! $this->enabled($type)) {
            return;
        }

        $owners = $this->owners();
        if ($owners->isNotEmpty()) {
            Notification::send($owners, $notification);
        }
    }
}
