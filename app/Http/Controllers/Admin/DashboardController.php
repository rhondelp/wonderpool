<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\ReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Admin landing page: headline figures, charts (M7) and upcoming bookings.
 */
class DashboardController extends Controller
{
    /** Months shown in the dashboard charts (current month included). */
    private const CHART_MONTHS = 6;

    /**
     * Dashboard with the last six months of revenue (financial users only) and confirmed stays,
     * plus the next 30 days of occupancy.
     */
    public function __invoke(DashboardService $dashboard, ReportService $reports): View
    {
        $to = now()->endOfMonth();
        $from = now()->startOfMonth()->subMonths(self::CHART_MONTHS - 1);

        return view('admin.dashboard', [
            'summary' => $dashboard->summary(),
            'upcoming' => $dashboard->upcoming(),
            'months' => $reports->series($from, $to, granularity: 'month'),
            'strip' => $reports->occupancyStrip(30),
            'showFinancials' => Gate::allows('view-financials'),
        ]);
    }
}
