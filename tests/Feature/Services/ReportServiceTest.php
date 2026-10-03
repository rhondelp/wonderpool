<?php

/*
| M7: report figures (D-034) — revenue by verified_at, stays/occupancy by stay date, series, packages, strip.
*/

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->reports = app(ReportService::class);
    $this->day = dayPackage();
    $this->night = nightPackage();

    // October 2026: two confirmed stays on Oct 3 (day + night), one completed on Oct 10, one pending, one cancelled.
    $this->a = Booking::factory()->for($this->day)->approved()->window(Carbon::parse('2026-10-03 07:00'), Carbon::parse('2026-10-03 17:00'))->create(['total_amount_cents' => 700_000]);
    $this->b = Booking::factory()->for($this->night)->approved()->window(Carbon::parse('2026-10-03 19:00'), Carbon::parse('2026-10-04 05:00'))->create(['total_amount_cents' => 900_000]);
    $this->c = Booking::factory()->for($this->day)->completed()->window(Carbon::parse('2026-10-10 07:00'), Carbon::parse('2026-10-10 17:00'))->create(['total_amount_cents' => 800_000]);
    Booking::factory()->for($this->day)->window(Carbon::parse('2026-10-20 07:00'), Carbon::parse('2026-10-20 17:00'))->create(['total_amount_cents' => 700_000]);
    Booking::factory()->for($this->night)->cancelled()->window(Carbon::parse('2026-10-21 19:00'), Carbon::parse('2026-10-22 05:00'))->create(['total_amount_cents' => 900_000]);
    // Outside the range.
    Booking::factory()->for($this->day)->approved()->window(Carbon::parse('2026-11-02 07:00'), Carbon::parse('2026-11-02 17:00'))->create(['total_amount_cents' => 700_000]);

    Payment::factory()->for($this->a)->verified()->create(['amount_cents' => 350_000, 'verified_at' => '2026-09-28 10:00']); // paid in September
    Payment::factory()->for($this->b)->verified()->create(['amount_cents' => 450_000, 'verified_at' => '2026-10-01 00:00']);
    Payment::factory()->for($this->c)->verified()->create(['amount_cents' => 800_000, 'verified_at' => '2026-10-31 23:59']);
    Payment::factory()->for($this->c)->create(['amount_cents' => 1_000]); // pending: ignored
    Payment::factory()->for($this->b)->rejected()->create(['amount_cents' => 2_000, 'verified_at' => '2026-10-05 10:00']); // voided: ignored

    $this->from = CarbonImmutable::parse('2026-10-01');
    $this->to = CarbonImmutable::parse('2026-10-31');
});

it('summarises revenue received, stays, booked value and occupancy', function () {
    $summary = $this->reports->summary($this->from, $this->to);

    expect($summary)->toMatchArray([
        'revenue_cents' => 1_250_000,
        'payments' => 2,
        'bookings' => 5,
        'confirmed' => 3,
        'booked_value_cents' => 2_400_000,
        'average_cents' => 800_000,
        'occupied_days' => 2,
        'days' => 31,
        'occupancy_percent' => 6,
    ])->and($summary['by_status'])->toBe([
        'pending' => 1, 'approved' => 2, 'rejected' => 0, 'cancelled' => 1, 'completed' => 1,
    ]);
});

it('filters every figure by package', function () {
    $summary = $this->reports->summary($this->from, $this->to, $this->night->id);

    expect($summary['revenue_cents'])->toBe(450_000)
        ->and($summary['confirmed'])->toBe(1)
        ->and($summary['booked_value_cents'])->toBe(900_000)
        ->and($summary['bookings'])->toBe(2);
});

it('returns zeros for an empty range', function () {
    $summary = $this->reports->summary(CarbonImmutable::parse('2025-01-01'), CarbonImmutable::parse('2025-01-31'));

    expect($summary['revenue_cents'])->toBe(0)->and($summary['average_cents'])->toBe(0)->and($summary['occupancy_percent'])->toBe(0);
});

it('builds a gap-filled daily series for short ranges', function () {
    $series = $this->reports->series($this->from, $this->to);

    expect($series)->toHaveCount(31)
        ->and($series[0])->toBe(['key' => '2026-10-01', 'label' => 'Oct 1', 'revenue_cents' => 450_000, 'bookings' => 0])
        ->and($series[2]['bookings'])->toBe(2)
        ->and($series[30]['revenue_cents'])->toBe(800_000)
        ->and(array_sum(array_column($series, 'revenue_cents')))->toBe(1_250_000);
});

it('builds a monthly series for long ranges', function () {
    $series = $this->reports->series(CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-12-31'));

    expect($this->reports->granularity(CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-12-31')))->toBe('month')
        ->and(array_column($series, 'key'))->toBe(['2026-09-01', '2026-10-01', '2026-11-01', '2026-12-01'])
        ->and(array_column($series, 'revenue_cents'))->toBe([350_000, 1_250_000, 0, 0])
        ->and(array_column($series, 'bookings'))->toBe([0, 3, 1, 0]);
});

it('breaks figures down per package and hides idle inactive packages', function () {
    dayPackage(['code' => 'OLD', 'name' => 'Retired', 'is_active' => false]);

    $rows = collect($this->reports->byPackage($this->from, $this->to))->keyBy(fn ($r) => $r['package']->code);

    expect($rows->keys()->all())->toEqualCanonicalizing(['DAY-A', 'NIGHT-D'])
        ->and($rows['DAY-A'])->toMatchArray(['bookings' => 2, 'booked_value_cents' => 1_500_000, 'revenue_cents' => 800_000])
        ->and($rows['NIGHT-D'])->toMatchArray(['bookings' => 1, 'booked_value_cents' => 900_000, 'revenue_cents' => 450_000]);
});

it('streams booking rows with verified amounts', function () {
    $rows = $this->reports->bookingRows($this->from, $this->to)->all();

    expect($rows)->toHaveCount(5)
        ->and($rows[0]->id)->toBe($this->a->id)
        ->and((int) $rows[2]->verified_cents)->toBe(800_000);
});

it('marks the next days as confirmed, pending, closed or free', function () {
    $now = Carbon::parse('2026-10-02 15:00');
    BlockedDate::factory()->create(['starts_at' => '2026-10-05 00:00', 'ends_at' => '2026-10-06 00:00']);
    Booking::factory()->for($this->day)->window(Carbon::parse('2026-10-04 07:00'), Carbon::parse('2026-10-04 17:00'))->create(); // pending

    $strip = collect($this->reports->occupancyStrip(5, $now))->mapWithKeys(fn ($d) => [$d['date']->format('m-d') => $d['state']]);

    expect($strip->all())->toBe([
        '10-02' => 'free',
        '10-03' => 'confirmed',
        '10-04' => 'pending',
        '10-05' => 'closed',
        '10-06' => 'free',
    ]);
});
