<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Queues pre-stay reminder emails (M8, D-038). Idempotent: a booking is reminded at most once
 * (bookings.reminded_at). Scheduled daily at config('wonderpool.notifications.reminder_time')
 * Asia/Manila in routes/console.php.
 */
class SendBookingReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'bookings:send-reminders {--dry-run : Only report how many reminders are due}';

    /**
     * @var string
     */
    protected $description = 'Email guests whose approved stay starts soon (once per booking)';

    /**
     * Queues the reminders and prints the count.
     */
    public function handle(NotificationService $notifications): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = $notifications->sendReminders(null, $dryRun);

        $this->info($dryRun
            ? "{$count} reminder(s) due."
            : "{$count} reminder(s) queued.");

        return self::SUCCESS;
    }
}
