<?php

namespace App\Notifications;

use App\Enums\ActivityAction;
use App\Notifications\Channels\NotificationChannelResolver;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * "Send test email" from Settings → Notifications (M8). Queued like every other email so it also
 * proves the queue worker runs; a failure is written to the activity log.
 */
class TestEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /** Single attempt: the owner wants a quick answer. */
    public int $tries = 1;

    /**
     * Mail only (default channels).
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return array_values(array_intersect(app(NotificationChannelResolver::class)->channels(null, $notifiable), ['mail']));
    }

    /**
     * The email (mail/test.blade.php).
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resort = (string) (app(SettingService::class)->get('general.resort_name') ?: config('app.name'));

        return (new MailMessage())
            ->subject('Test email · '.$resort)
            ->markdown('mail.test', ['resort' => $resort, 'sentAt' => now()]);
    }

    /**
     * Queue gave up: audit entry (recipient address removed from the error).
     */
    public function failed(Throwable $e): void
    {
        $message = (string) preg_replace('/[^\s@<>"]+@[^\s@<>"]+/', '[email]', $e->getMessage());

        app(ActivityLogger::class)->log(ActivityAction::MailFailed, null, [
            'notification' => 'test',
            'error' => Str::limit(class_basename($e).': '.$message, 300),
        ], null);
    }
}
