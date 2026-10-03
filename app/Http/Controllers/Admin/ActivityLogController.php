<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityLogRequest;
use App\Services\ActivityLogService;
use Illuminate\View\View;

/**
 * Owner-only audit trail: who did what and when (M7, D-036). Read-only.
 */
class ActivityLogController extends Controller
{
    /**
     * Filterable, paginated activity log.
     */
    public function __invoke(ActivityLogRequest $request, ActivityLogService $logs): View
    {
        return view('admin.activity-log.index', [
            'entries' => $logs->listing($request->filters()),
            'logs' => $logs,
            'actors' => $logs->actorOptions(),
            'modules' => ActivityAction::modules(),
            'actions' => collect(ActivityAction::cases())->mapWithKeys(fn (ActivityAction $a): array => [$a->value => $a->label()])->sort()->all(),
        ]);
    }
}
