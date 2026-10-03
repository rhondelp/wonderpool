<?php

/*
| M7: reports page, CSV/PDF exports (D-035), access, validation, dashboard charts.
*/

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create();
    $this->day = dayPackage();
    $this->booking = Booking::factory()->for($this->day)->approved()
        ->window(Carbon::parse('2026-10-03 07:00'), Carbon::parse('2026-10-03 17:00'))
        ->create(['reference_code' => 'WP-2610-ABCD', 'guest_name' => '=HYPERLINK("x")', 'guest_phone' => '+639171234567', 'total_amount_cents' => 700_000]);
    Payment::factory()->for($this->booking)->verified()->create(['amount_cents' => 350_000, 'verified_at' => '2026-10-02 10:00']);
});

it('shows the report for a range', function () {
    $this->actingAs($this->owner)->get('/admin/reports?from=2026-10-01&to=2026-10-31')
        ->assertOk()
        ->assertSee('Revenue received')
        ->assertSee('₱3,500.00')
        ->assertSee('₱7,000.00')
        ->assertSee('Day Package (A)')
        ->assertSee('Revenue received per day');
});

it('defaults to the current month', function () {
    Carbon::setTestNow('2026-10-15 09:00');

    $this->actingAs($this->owner)->get('/admin/reports')->assertOk()->assertSee('Oct 1, 2026')->assertSee('Oct 31, 2026');

    Carbon::setTestNow();
});

it('rejects invalid or too long ranges', function (string $query) {
    $this->actingAs($this->owner)->get('/admin/reports?'.$query)->assertSessionHasErrors();
})->with([
    'to before from' => ['from=2026-10-10&to=2026-10-01'],
    'bad date' => ['from=10/01/2026'],
    'over two years' => ['from=2024-01-01&to=2026-12-31'],
    'unknown package' => ['package=9999'],
]);

it('is owner only, including exports and the nav link', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)->get('/admin/reports')->assertForbidden();
    $this->actingAs($staff)->get('/admin/reports/export.csv')->assertForbidden();
    $this->actingAs($staff)->get('/admin/reports/export.pdf')->assertForbidden();
    $this->actingAs($staff)->get('/admin')->assertOk()->assertDontSee(route('admin.reports.index'))->assertDontSee('Revenue received');
    $this->actingAs($this->owner)->get('/admin')->assertOk()->assertSee(route('admin.reports.index'));
});

it('exports bookings as a formula-safe CSV and logs the export', function () {
    $response = $this->actingAs($this->owner)->get('/admin/reports/export.csv?from=2026-10-01&to=2026-10-31')->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('wonderpool-report-2026-10-01-to-2026-10-31.csv');
    $csv = $response->streamedContent();
    $lines = array_map('str_getcsv', explode("\n", trim(substr($csv, 3))));

    expect(substr($csv, 0, 3))->toBe("\xEF\xBB\xBF")
        ->and($lines[0])->toBe(ReportExportService::CSV_HEADERS)
        ->and($lines)->toHaveCount(2)
        ->and($lines[1][0])->toBe('WP-2610-ABCD')
        ->and($lines[1][5])->toBe("'=HYPERLINK(\"x\")")
        ->and($lines[1][6])->toBe("'+639171234567")
        ->and(array_slice($lines[1], 11, 3))->toBe(['7000.00', '3500.00', '3500.00']);

    $log = ActivityLog::query()->where('action', ActivityAction::ReportExported->value)->sole();
    expect($log->user_id)->toBe($this->owner->id)
        ->and($log->properties)->toHaveCount(3)->toMatchArray(['format' => 'csv', 'from' => '2026-10-01', 'to' => '2026-10-31']); // jsonb: key order not kept
});

it('downloads the summary PDF', function () {
    $response = $this->actingAs($this->owner)->get("/admin/reports/export.pdf?from=2026-10-01&to=2026-10-31&package={$this->day->id}")->assertOk();

    expect($response->headers->get('content-type'))->toBe('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF');
    expect(ActivityLog::query()->where('action', 'report.exported')->sole()->properties['package_id'])->toBe($this->day->id);
});

it('draws dashboard charts and the occupancy strip', function () {
    Carbon::setTestNow('2026-10-02 08:00');

    $this->actingAs($this->owner)->get('/admin')->assertOk()
        ->assertSee('Revenue received')
        ->assertSee('Confirmed stays')
        ->assertSee('Next 30 days')
        ->assertSee('Saturday, October 3: Confirmed stay')
        ->assertSee('Oct 2026: ₱3,500.00');

    Carbon::setTestNow();
});
