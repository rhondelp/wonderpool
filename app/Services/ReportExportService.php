<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonInterface;

/**
 * Report exports (D-035): bookings CSV (UTF-8 with BOM for Excel, pesos as plain decimals,
 * formula-injection safe) and the data for the PDF summary. Every export is audit-logged.
 */
class ReportExportService
{
    /** CSV column headers, in order. */
    public const CSV_HEADERS = [
        'Reference', 'Stay date', 'Starts', 'Ends', 'Package', 'Guest', 'Phone', 'Email', 'Guests',
        'Status', 'Source', 'Total (PHP)', 'Verified paid (PHP)', 'Balance (PHP)', 'Booked at',
    ];

    /**
     * @param  ReportService  $reports  Figures and booking rows
     * @param  ActivityLogger  $logger  Audit trail
     */
    public function __construct(
        private readonly ReportService $reports,
        private readonly ActivityLogger $logger,
    ) {
    }

    /**
     * Writes the bookings CSV (header + one row per booking starting in the range) to a stream.
     *
     * @param  resource  $handle  Writable stream, e.g. php://output
     * @param  CarbonInterface  $from  First day (inclusive)
     * @param  CarbonInterface  $to  Last day (inclusive)
     * @param  int|null  $packageId  Limit to one package
     */
    public function writeCsv($handle, CarbonInterface $from, CarbonInterface $to, ?int $packageId = null): void
    {
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::CSV_HEADERS, escape: '');

        foreach ($this->reports->bookingRows($from, $to, $packageId) as $booking) {
            fputcsv($handle, array_map(self::safeCell(...), $this->csvRow($booking)), escape: '');
        }
    }

    /**
     * One booking as CSV cells.
     *
     * @return list<string>
     */
    public function csvRow(Booking $booking): array
    {
        $paid = (int) ($booking->getAttribute('verified_cents') ?? 0);

        return [
            $booking->reference_code,
            $booking->starts_at->format('Y-m-d'),
            $booking->starts_at->format('Y-m-d H:i'),
            $booking->ends_at->format('Y-m-d H:i'),
            $booking->package->name ?? '',
            $booking->guest_name,
            $booking->guest_phone,
            (string) $booking->guest_email,
            (string) $booking->guest_count,
            $booking->status->label(),
            $booking->source->label(),
            Money::decimal($booking->total_amount_cents),
            Money::decimal($paid),
            Money::decimal(max(0, $booking->total_amount_cents - $paid)),
            $booking->created_at?->format('Y-m-d H:i') ?? '',
        ];
    }

    /**
     * Everything the PDF summary shows.
     *
     * @return array{from: CarbonInterface, to: CarbonInterface, package: Package|null, summary: array<string, mixed>, series: list<array<string, mixed>>, packages: list<array<string, mixed>>, granularity: string}
     */
    public function pdfData(CarbonInterface $from, CarbonInterface $to, ?int $packageId = null): array
    {
        return [
            'from' => $from,
            'to' => $to,
            'package' => $packageId !== null ? Package::query()->find($packageId) : null,
            'summary' => $this->reports->summary($from, $to, $packageId),
            'series' => $this->reports->series($from, $to, $packageId),
            'packages' => $packageId === null ? $this->reports->byPackage($from, $to) : [],
            'granularity' => $this->reports->granularity($from, $to),
        ];
    }

    /**
     * Download file name, e.g. wonderpool-report-2026-10-01-to-2026-10-31.csv.
     */
    public function filename(CarbonInterface $from, CarbonInterface $to, string $extension): string
    {
        return "wonderpool-report-{$from->format('Y-m-d')}-to-{$to->format('Y-m-d')}.{$extension}";
    }

    /**
     * Records who exported what (guest personal data leaves the system in the CSV).
     */
    public function logExport(User $actor, string $format, CarbonInterface $from, CarbonInterface $to, ?int $packageId = null): void
    {
        $this->logger->log(ActivityAction::ReportExported, null, array_filter([
            'format' => $format,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'package_id' => $packageId,
        ], fn ($value): bool => $value !== null), $actor);
    }

    /**
     * Neutralises spreadsheet formulas: cells starting with = + - @ tab or CR get a leading apostrophe.
     */
    public static function safeCell(string $value): string
    {
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
