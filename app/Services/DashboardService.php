<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Figures for the admin dashboard shell. Charts and reports come in M7.
 * All dates use the app timezone (Asia/Manila, D-009).
 */
class DashboardService
{
    /**
     * Headline counts and this month's verified revenue.
     *
     * @param  Carbon|null  $now  Reference time (default: now), injectable for tests
     * @return array{pending: int, arrivals_today: int, upcoming_week: int, revenue_month_cents: int}
     */
    public function summary(?Carbon $now = null): array
    {
        $now ??= now();
        $today = $now->copy()->startOfDay();

        return [
            'pending' => Booking::query()->status(BookingStatus::Pending)->count(),
            'arrivals_today' => Booking::query()
                ->active()
                ->where('starts_at', '>=', $today)
                ->where('starts_at', '<', $today->copy()->addDay())
                ->count(),
            'upcoming_week' => Booking::query()
                ->active()
                ->where('starts_at', '>=', $now)
                ->where('starts_at', '<', $today->copy()->addDays(8))
                ->count(),
            'revenue_month_cents' => (int) Payment::query()
                ->verified()
                ->where('verified_at', '>=', $now->copy()->startOfMonth())
                ->where('verified_at', '<', $now->copy()->startOfMonth()->addMonth())
                ->sum('amount_cents'),
        ];
    }

    /**
     * Next pending/approved bookings that have not started yet, soonest first.
     *
     * @param  int  $limit  Maximum rows
     * @param  Carbon|null  $now  Reference time (default: now)
     * @return Collection<int, Booking>
     */
    public function upcoming(int $limit = 5, ?Carbon $now = null): Collection
    {
        return Booking::query()
            ->with('package')
            ->active()
            ->where('starts_at', '>=', $now ?? now())
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }
}
