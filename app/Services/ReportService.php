<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

/**
 * Figures for the reports page, its exports and the dashboard charts (D-034).
 *
 * - Revenue = verified payments, dated by `verified_at` (cash received; same rule as the dashboard).
 * - Bookings, booked value and occupancy are dated by the stay start (`starts_at`).
 * - "Confirmed" = approved + completed. Ranges are whole local days, `to` inclusive (D-009).
 */
class ReportService
{
    /** Longest range a report may cover, in days. */
    public const MAX_RANGE_DAYS = 731;

    /** Ranges up to this many days are charted per day; longer ones per month. */
    public const DAILY_LIMIT_DAYS = 62;

    /**
     * Statuses counted as confirmed stays.
     *
     * @return list<BookingStatus>
     */
    public static function confirmedStatuses(): array
    {
        return [BookingStatus::Approved, BookingStatus::Completed];
    }

    /**
     * Headline figures for a range.
     *
     * @param  CarbonInterface  $from  First day (inclusive)
     * @param  CarbonInterface  $to  Last day (inclusive)
     * @param  int|null  $packageId  Limit to one package
     * @return array{revenue_cents: int, payments: int, bookings: int, by_status: array<string, int>, confirmed: int, booked_value_cents: int, average_cents: int, occupied_days: int, days: int, occupancy_percent: int}
     */
    public function summary(CarbonInterface $from, CarbonInterface $to, ?int $packageId = null): array
    {
        [$start, $end] = $this->bounds($from, $to);

        $revenue = $this->payments($start, $end, $packageId)
            ->selectRaw('COALESCE(SUM(amount_cents), 0) AS total, COUNT(*) AS payments')
            ->toBase()
            ->first();

        $counts = $this->bookings($start, $end, $packageId)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->toBase()
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        $byStatus = [];
        foreach (BookingStatus::cases() as $status) {
            $byStatus[$status->value] = $counts->get($status->value, 0);
        }

        $confirmed = $this->bookings($start, $end, $packageId)
            ->whereIn('status', self::confirmedStatuses())
            ->selectRaw('COUNT(*) AS bookings, COALESCE(SUM(total_amount_cents), 0) AS value, COUNT(DISTINCT DATE(starts_at)) AS days')
            ->toBase()
            ->first();

        $confirmedCount = (int) ($confirmed->bookings ?? 0);
        $bookedValue = (int) ($confirmed->value ?? 0);
        $occupiedDays = (int) ($confirmed->days ?? 0);
        $days = $this->days($from, $to);

        return [
            'revenue_cents' => (int) ($revenue->total ?? 0),
            'payments' => (int) ($revenue->payments ?? 0),
            'bookings' => array_sum($byStatus),
            'by_status' => $byStatus,
            'confirmed' => $confirmedCount,
            'booked_value_cents' => $bookedValue,
            'average_cents' => $confirmedCount > 0 ? intdiv($bookedValue, $confirmedCount) : 0,
            'occupied_days' => $occupiedDays,
            'days' => $days,
            'occupancy_percent' => $days > 0 ? (int) round($occupiedDays * 100 / $days) : 0,
        ];
    }

    /**
     * Revenue and confirmed stays per day (ranges ≤ DAILY_LIMIT_DAYS) or per month, gaps filled with zeros.
     *
     * @param  CarbonInterface  $from  First day (inclusive)
     * @param  CarbonInterface  $to  Last day (inclusive)
     * @param  int|null  $packageId  Limit to one package
     * @param  string|null  $granularity  Force 'day' or 'month'
     * @return list<array{key: string, label: string, revenue_cents: int, bookings: int}>
     */
    public function series(CarbonInterface $from, CarbonInterface $to, ?int $packageId = null, ?string $granularity = null): array
    {
        $granularity ??= $this->granularity($from, $to);
        $unit = $granularity === 'day' ? 'day' : 'month';
        [$start, $end] = $this->bounds($from, $to);

        $revenue = $this->payments($start, $end, $packageId)
            ->selectRaw("DATE_TRUNC('{$unit}', verified_at) AS bucket, SUM(amount_cents) AS total")
            ->groupByRaw('1')
            ->toBase()
            ->pluck('total', 'bucket')
            ->mapWithKeys(fn ($total, $bucket): array => [substr((string) $bucket, 0, 10) => (int) $total]);

        $stays = $this->bookings($start, $end, $packageId)
            ->whereIn('status', self::confirmedStatuses())
            ->selectRaw("DATE_TRUNC('{$unit}', starts_at) AS bucket, COUNT(*) AS total")
            ->groupByRaw('1')
            ->toBase()
            ->pluck('total', 'bucket')
            ->mapWithKeys(fn ($total, $bucket): array => [substr((string) $bucket, 0, 10) => (int) $total]);

        $rows = [];
        $cursor = $unit === 'day' ? $start : $start->startOfMonth();
        while ($cursor < $end) {
            $key = $cursor->format('Y-m-d');
            $rows[] = [
                'key' => $key,
                'label' => $unit === 'day' ? $cursor->format('M j') : $cursor->format('M Y'),
                'revenue_cents' => $revenue->get($key, 0),
                'bookings' => $stays->get($key, 0),
            ];
            $cursor = $unit === 'day' ? $cursor->addDay() : $cursor->addMonth();
        }

        return $rows;
    }

    /**
     * Per-package breakdown: confirmed stays, booked value and revenue received. Packages with no
     * activity are listed only while active.
     *
     * @param  CarbonInterface  $from  First day (inclusive)
     * @param  CarbonInterface  $to  Last day (inclusive)
     * @return list<array{package: Package, bookings: int, booked_value_cents: int, revenue_cents: int}>
     */
    public function byPackage(CarbonInterface $from, CarbonInterface $to): array
    {
        [$start, $end] = $this->bounds($from, $to);

        $stays = $this->bookings($start, $end)
            ->whereIn('status', self::confirmedStatuses())
            ->selectRaw('package_id, COUNT(*) AS bookings, SUM(total_amount_cents) AS value')
            ->groupBy('package_id')
            ->toBase()
            ->get()
            ->keyBy('package_id');

        $revenue = Payment::query()
            ->where('payments.status', PaymentStatus::Verified)
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->where('payments.verified_at', '>=', $start)
            ->where('payments.verified_at', '<', $end)
            ->selectRaw('bookings.package_id, SUM(payments.amount_cents) AS total')
            ->groupBy('bookings.package_id')
            ->toBase()
            ->pluck('total', 'package_id');

        $rows = [];
        foreach (Package::query()->orderBy('sort_order')->orderBy('id')->get() as $package) {
            $row = [
                'package' => $package,
                'bookings' => (int) ($stays->get($package->id)->bookings ?? 0),
                'booked_value_cents' => (int) ($stays->get($package->id)->value ?? 0),
                'revenue_cents' => (int) $revenue->get($package->id, 0),
            ];

            if ($package->is_active || $row['bookings'] > 0 || $row['revenue_cents'] > 0) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Bookings whose stay starts in the range (all statuses), with verified payments summed, for CSV export.
     *
     * @param  CarbonInterface  $from  First day (inclusive)
     * @param  CarbonInterface  $to  Last day (inclusive)
     * @param  int|null  $packageId  Limit to one package
     * @return LazyCollection<int, Booking>
     */
    public function bookingRows(CarbonInterface $from, CarbonInterface $to, ?int $packageId = null): LazyCollection
    {
        [$start, $end] = $this->bounds($from, $to);

        return $this->bookings($start, $end, $packageId)
            ->with('package:id,name')
            ->withSum(['payments as verified_cents' => fn (Builder $q) => $q->where('status', PaymentStatus::Verified)], 'amount_cents')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->lazy(500);
    }

    /**
     * Next days for the dashboard strip: each date is confirmed (approved stay starts), pending,
     * closed (blocked) or free. Confirmed wins over pending, pending over closed.
     *
     * @param  int  $days  Number of days from today
     * @param  CarbonInterface|null  $now  Reference time (default: now)
     * @return list<array{date: CarbonImmutable, state: string}>
     */
    public function occupancyStrip(int $days = 30, ?CarbonInterface $now = null): array
    {
        $start = CarbonImmutable::instance($now ?? now())->startOfDay();
        $end = $start->addDays($days);

        $bookings = Booking::query()
            ->active()
            ->where('starts_at', '>=', $start)
            ->where('starts_at', '<', $end)
            ->get(['starts_at', 'status']);

        $blocks = BlockedDate::query()->overlapping($start, $end)->get(['starts_at', 'ends_at']);

        $strip = [];
        for ($date = $start; $date < $end; $date = $date->addDay()) {
            $next = $date->addDay();
            $onDay = $bookings->filter(fn (Booking $b): bool => $b->starts_at >= $date && $b->starts_at < $next);

            $state = match (true) {
                $onDay->contains(fn (Booking $b): bool => $b->status === BookingStatus::Approved) => 'confirmed',
                $onDay->isNotEmpty() => 'pending',
                $blocks->contains(fn (BlockedDate $b): bool => $b->starts_at < $next && $b->ends_at > $date) => 'closed',
                default => 'free',
            };

            $strip[] = ['date' => $date, 'state' => $state];
        }

        return $strip;
    }

    /**
     * 'day' for short ranges, otherwise 'month'.
     */
    public function granularity(CarbonInterface $from, CarbonInterface $to): string
    {
        return $this->days($from, $to) <= self::DAILY_LIMIT_DAYS ? 'day' : 'month';
    }

    /**
     * Number of whole days in the inclusive range.
     */
    public function days(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) CarbonImmutable::instance($from)->startOfDay()->diffInDays(CarbonImmutable::instance($to)->startOfDay()) + 1;
    }

    /**
     * Half-open query bounds [from 00:00, to + 1 day 00:00).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function bounds(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            CarbonImmutable::instance($from)->startOfDay(),
            CarbonImmutable::instance($to)->startOfDay()->addDay(),
        ];
    }

    /**
     * Verified payments received in the bounds.
     *
     * @return Builder<Payment>
     */
    private function payments(CarbonImmutable $start, CarbonImmutable $end, ?int $packageId = null): Builder
    {
        return Payment::query()
            ->verified()
            ->where('verified_at', '>=', $start)
            ->where('verified_at', '<', $end)
            ->when($packageId !== null, fn (Builder $q) => $q->whereIn(
                'booking_id',
                Booking::withTrashed()->select('id')->where('package_id', $packageId),
            ));
    }

    /**
     * Bookings whose stay starts in the bounds.
     *
     * @return Builder<Booking>
     */
    private function bookings(CarbonImmutable $start, CarbonImmutable $end, ?int $packageId = null): Builder
    {
        return Booking::query()
            ->where('starts_at', '>=', $start)
            ->where('starts_at', '<', $end)
            ->when($packageId !== null, fn (Builder $q) => $q->where('package_id', $packageId));
    }
}
