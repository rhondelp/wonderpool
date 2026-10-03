<?php

namespace App\Notifications;

use App\Enums\ActivityAction;
use App\Enums\NotificationType;
use App\Models\Booking;
use App\Notifications\Channels\NotificationChannelResolver;
use App\Services\ActivityLogger;
use App\Services\Booking\GuestBookingService;
use App\Services\SettingService;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Base for every booking email (M8, D-037): always queued, dispatched only after the surrounding
 * transaction commits, channels from NotificationChannelResolver, retried 3 times, and a final
 * failure is written to the activity log (mail.failed).
 *
 * Emails never contain payment proof links, phone numbers or other sensitive data; guests get the
 * public /track link, owners a link to the (login-protected) admin booking page.
 */
abstract class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Attempts before giving up (then failed() logs it). */
    public int $tries = 3;

    /** @var list<int> Seconds between attempts */
    public array $backoff = [60, 300];

    /**
     * @param  Booking  $booking  Booking the email is about (re-loaded fresh by the queue worker)
     */
    public function __construct(public Booking $booking)
    {
        $this->afterCommit();
    }

    /**
     * Which notification this is (toggle + channel lookup).
     */
    abstract public function type(): NotificationType;

    /**
     * The email.
     */
    abstract public function toMail(object $notifiable): MailMessage;

    /**
     * Channels for this recipient.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return app(NotificationChannelResolver::class)->channels($this->type(), $notifiable);
    }

    /**
     * Called by the queue after the last attempt failed: audit entry without the recipient's address.
     */
    public function failed(Throwable $e): void
    {
        $message = (string) preg_replace('/[^\s@<>"]+@[^\s@<>"]+/', '[email]', $e->getMessage());

        app(ActivityLogger::class)->log(ActivityAction::MailFailed, $this->booking, [
            'notification' => $this->type()->value,
            'reference' => $this->booking->reference_code,
            'error' => Str::limit(class_basename($e).': '.$message, 300),
        ], null);
    }

    /**
     * Booking facts shared by the templates (no phone, email or payment proof).
     *
     * @return array{reference: string, package: string, schedule: string, guests: int, total: string, downpayment: string}
     */
    protected function details(): array
    {
        $booking = $this->booking->loadMissing('package');

        return [
            'reference' => $booking->reference_code,
            'package' => $booking->package->name,
            'schedule' => app(GuestBookingService::class)->windowLabel($booking->starts_at, $booking->ends_at),
            'guests' => $booking->guest_count,
            'total' => Money::format($booking->total_amount_cents),
            'downpayment' => Money::format($booking->downpayment_required_cents),
        ];
    }

    /**
     * Resort name for subjects.
     */
    protected function resortName(): string
    {
        return (string) (app(SettingService::class)->get('general.resort_name') ?: config('app.name'));
    }

    /**
     * Markdown email with the shared view data.
     *
     * @param  string  $subject  Subject line (the resort name is appended)
     * @param  string  $view  Template under resources/views/mail/bookings
     * @param  array<string, mixed>  $data  Extra template data
     */
    protected function message(string $subject, string $view, array $data = []): MailMessage
    {
        return (new MailMessage())
            ->subject($subject.' · '.$this->resortName())
            ->markdown('mail.bookings.'.$view, $data + [
                'booking' => $this->booking,
                'details' => $this->details(),
                'resort' => $this->resortName(),
            ]);
    }
}
