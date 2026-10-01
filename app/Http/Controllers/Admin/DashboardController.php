<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

/**
 * Admin landing page: headline figures and upcoming bookings.
 */
class DashboardController extends Controller
{
    /**
     * Dashboard shell (charts arrive in M7).
     */
    public function __invoke(DashboardService $dashboard): View
    {
        return view('admin.dashboard', [
            'summary' => $dashboard->summary(),
            'upcoming' => $dashboard->upcoming(),
        ]);
    }
}
