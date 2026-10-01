<?php

namespace App\Console\Commands;

use App\Services\Booking\BookingService;
use Illuminate\Console\Command;

/**
 * Cancels pending bookings with no payment proof after booking.pending_hold_hours (PLAN.md §5.3).
 * Scheduled every 15 minutes in routes/console.php.
 */
class ExpireStaleBookings extends Command
{
    /**
     * @var string
     */
    protected $signature = 'bookings:expire-stale {--dry-run : Only report how many bookings would expire}';

    /**
     * @var string
     */
    protected $description = 'Cancel unpaid pending bookings older than the pending hold time';

    /**
     * Runs the expiry and prints the count.
     */
    public function handle(BookingService $bookings): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = $bookings->expireStale(null, $dryRun);

        $this->info($dryRun
            ? "{$count} pending booking(s) would expire."
            : "{$count} pending booking(s) expired.");

        return self::SUCCESS;
    }
}
