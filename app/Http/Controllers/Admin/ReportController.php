<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Models\Package;
use App\Models\User;
use App\Services\ReportExportService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner reports (M7): revenue and bookings for a date range, per period and per package,
 * with CSV (bookings) and PDF (summary) exports (D-034, D-035).
 */
class ReportController extends Controller
{
    /**
     * Reports page.
     */
    public function index(ReportRequest $request, ReportService $reports): View
    {
        $from = $request->from();
        $to = $request->to();
        $packageId = $request->packageId();

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'packageId' => $packageId,
            'packages' => Package::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name']),
            'summary' => $reports->summary($from, $to, $packageId),
            'series' => $reports->series($from, $to, $packageId),
            'byPackage' => $packageId === null ? $reports->byPackage($from, $to) : [],
            'granularity' => $reports->granularity($from, $to),
        ]);
    }

    /**
     * Bookings in the range as CSV (streamed).
     */
    public function csv(ReportRequest $request, ReportExportService $exports): StreamedResponse
    {
        [$from, $to, $packageId] = [$request->from(), $request->to(), $request->packageId()];
        /** @var User $user */
        $user = $request->user();
        $exports->logExport($user, 'csv', $from, $to, $packageId);

        return response()->streamDownload(function () use ($exports, $from, $to, $packageId): void {
            $handle = fopen('php://output', 'w');
            if ($handle !== false) {
                $exports->writeCsv($handle, $from, $to, $packageId);
                fclose($handle);
            }
        }, $exports->filename($from, $to, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Summary as a PDF download.
     */
    public function pdf(ReportRequest $request, ReportExportService $exports): Response
    {
        [$from, $to, $packageId] = [$request->from(), $request->to(), $request->packageId()];
        /** @var User $user */
        $user = $request->user();
        $exports->logExport($user, 'pdf', $from, $to, $packageId);

        return Pdf::loadView('admin.reports.pdf', $exports->pdfData($from, $to, $packageId))
            ->setPaper('a4')
            ->download($exports->filename($from, $to, 'pdf'));
    }
}
