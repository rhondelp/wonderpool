<?php

namespace App\Services\Booking;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Package;
use App\Services\SettingService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Exclusive-use availability (PLAN.md §5.1, D-009). A window [S, E) is unavailable when it
 * overlaps a pending/approved booking or a blocked date; touching boundaries are allowed,
 * so a Day package (07:00–17:00) and a Night package (19:00–05:00) can share a date.
 */
class AvailabilityService
{
    /**
     * @param  SettingService  $settings  Lead time / max advance settings
     */
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * Concrete window of a package on a calendar date (local Asia/Manila wall-clock).
     * Packages that cross midnight end on the next day (Night 19:00 → 05:00 next day).
     *
     * @param  Package  $package  Package with HH:MM:SS start/end times
     * @param  CarbonInterface  $date  Any time on the booking's start date
     * @return array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}
     */
    public function resolveWindow(Package $package, CarbonInterface $date): array
    {
        $day = CarbonImmutable::parse($date->format('Y-m-d'), config('app.timezone'));
        $start = $day->setTimeFromTimeString($package->start_time);
        $end = $day->setTimeFromTimeString($package->end_time);

        if ($package->crosses_midnight || $end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return ['starts_at' => $start, 'ends_at' => $end];
    }

    /**
     * Whether [$start, $end) is free of active bookings and blocked dates.
     *
     * @param  int|null  $ignoreBookingId  Booking to leave out (rescheduling itself)
     */
    public function isAvailable(CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): bool
    {
        return ! $this->bookingsQuery($start, $end, $ignoreBookingId)->exists()
            && ! BlockedDate::query()->overlapping($start, $end)->exists();
    }

    /**
     * What occupies [$start, $end): overlapping active bookings and blocked dates.
     *
     * @param  int|null  $ignoreBookingId  Booking to leave out
     * @return array{bookings: Collection<int, Booking>, blocks: Collection<int, BlockedDate>}
     */
    public function conflicts(CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): array
    {
        return [
            'bookings' => $this->bookingsQuery($start, $end, $ignoreBookingId)->orderBy('starts_at')->get(),
            'blocks' => BlockedDate::query()->overlapping($start, $end)->orderBy('starts_at')->get(),
        ];
    }

    /**
     * Per-day availability for a calendar between two dates (inclusive), using two queries.
     * With a package: whether that package's window is free each day. Without: every active
     * package is checked and a day counts as available when at least one package fits.
     *
     * @param  CarbonInterface  $from  First date
     * @param  CarbonInterface  $to  Last date (inclusive)
     * @param  Package|null  $package  Restrict to one package
     * @param  int|null  $ignoreBookingId  Booking to leave out (admin rescheduling, M6)
     * @return array<string, array{available: bool, packages: array<int, bool>}> Keyed by Y-m-d
     */
    public function unavailableDates(CarbonInterface $from, CarbonInterface $to, ?Package $package = null, ?int $ignoreBookingId = null): array
    {
        $first = CarbonImmutable::parse($from->format('Y-m-d'), config('app.timezone'));
        $last = CarbonImmutable::parse($to->format('Y-m-d'), config('app.timezone'));
        $packages = $package !== null ? collect([$package]) : Package::query()->active()->ordered()->get();

        // Windows can start on $last and end the next morning, so look one extra day ahead.
        $rangeEnd = $last->addDays(2);
        $busy = $this->bookingsQuery($first, $rangeEnd, $ignoreBookingId)->get(['starts_at', 'ends_at'])
            ->concat(BlockedDate::query()->overlapping($first, $rangeEnd)->get(['starts_at', 'ends_at']))
            ->map(fn ($row): array => [$row->starts_at->getTimestamp(), $row->ends_at->getTimestamp()])
            ->all();

        $map = [];
        for ($day = $first; $day->lessThanOrEqualTo($last); $day = $day->addDay()) {
            $perPackage = [];
            foreach ($packages as $candidate) {
                $window = $this->resolveWindow($candidate, $day);
                $perPackage[$candidate->id] = $this->isFreeIn($busy, $window['starts_at']->getTimestamp(), $window['ends_at']->getTimestamp());
            }

            $map[$day->format('Y-m-d')] = [
                'available' => in_array(true, $perPackage, true),
                'packages' => $perPackage,
            ];
        }

        return $map;
    }

    /**
     * Whether a start time respects the booking lead time and maximum advance settings
     * (booking.lead_time_hours, booking.max_advance_days). Used by guest-facing requests (M5);
     * not enforced by BookingService::create so admins can record walk-ins.
     *
     * @param  CarbonInterface|null  $now  Reference time (default: now)
     */
    public function isWithinBookingWindow(CarbonInterface $start, ?CarbonInterface $now = null): bool
    {
        $now = CarbonImmutable::instance($now ?? now());
        $earliest = $now->addHours($this->settings->int('booking.lead_time_hours', 24));
        $latest = $now->addDays($this->settings->int('booking.max_advance_days', 365))->endOfDay();

        return $start->greaterThanOrEqualTo($earliest) && $start->lessThanOrEqualTo($latest);
    }

    /**
     * Active (pending/approved, not soft-deleted) bookings overlapping [$start, $end).
     *
     * @return \Illuminate\Database\Eloquent\Builder<Booking>
     */
    private function bookingsQuery(CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): \Illuminate\Database\Eloquent\Builder
    {
        return Booking::query()
            ->active()
            ->overlapping($start, $end)
            ->when($ignoreBookingId !== null, fn ($query) => $query->whereKeyNot($ignoreBookingId));
    }

    /**
     * Half-open overlap check of [$start, $end) against [start, end) timestamp pairs.
     *
     * @param  list<array{0: int, 1: int}>  $busy
     */
    private function isFreeIn(array $busy, int $start, int $end): bool
    {
        foreach ($busy as [$busyStart, $busyEnd]) {
            if ($busyStart < $end && $busyEnd > $start) {
                return false;
            }
        }

        return true;
    }
}
