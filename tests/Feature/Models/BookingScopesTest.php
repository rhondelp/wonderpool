<?php

use App\Models\BlockedDate;
use App\Models\Booking;
use Illuminate\Support\Carbon;

/*
| M1: Booking::overlapping() uses half-open windows [start, end).
| Existing booking: 2026-12-05 07:00 → 17:00 (Day package).
*/

beforeEach(function () {
    $this->existing = Booking::factory()->window(
        Carbon::parse('2026-12-05 07:00'),
        Carbon::parse('2026-12-05 17:00'),
    )->create();
});

function overlapIds(string $start, string $end): array
{
    return Booking::query()->overlapping(Carbon::parse($start), Carbon::parse($end))->pluck('id')->all();
}

it('does not treat touching boundaries as overlaps', function (string $start, string $end) {
    expect(overlapIds($start, $end))->toBe([]);
})->with([
    'ends exactly when existing starts' => ['2026-12-04 19:00', '2026-12-05 07:00'],
    'starts exactly when existing ends' => ['2026-12-05 17:00', '2026-12-06 05:00'],
    'Night after Day on the same date' => ['2026-12-05 19:00', '2026-12-06 05:00'],
    'entirely before' => ['2026-12-04 07:00', '2026-12-04 17:00'],
    'entirely after' => ['2026-12-06 07:00', '2026-12-06 17:00'],
]);

it('detects real overlaps', function (string $start, string $end) {
    expect(overlapIds($start, $end))->toBe([$this->existing->id]);
})->with([
    'identical window' => ['2026-12-05 07:00', '2026-12-05 17:00'],
    'inside existing' => ['2026-12-05 09:00', '2026-12-05 12:00'],
    'contains existing (24-Hour)' => ['2026-12-05 07:00', '2026-12-06 05:00'],
    'overlaps the start' => ['2026-12-04 19:00', '2026-12-05 08:00'],
    'overlaps the end' => ['2026-12-05 16:59', '2026-12-06 05:00'],
    'one minute inside the start' => ['2026-12-05 06:00', '2026-12-05 07:01'],
]);

it('limits active() to pending and approved bookings', function () {
    Booking::factory()->approved()->window(Carbon::parse('2026-12-10 07:00'), Carbon::parse('2026-12-10 17:00'))->create();
    Booking::factory()->rejected()->window(Carbon::parse('2026-12-05 09:00'), Carbon::parse('2026-12-05 12:00'))->create();
    Booking::factory()->cancelled()->window(Carbon::parse('2026-12-05 09:00'), Carbon::parse('2026-12-05 12:00'))->create();
    Booking::factory()->completed()->create();

    expect(Booking::query()->active()->count())->toBe(2)
        ->and(
            Booking::query()->active()
                ->overlapping(Carbon::parse('2026-12-05 00:00'), Carbon::parse('2026-12-06 00:00'))
                ->pluck('id')->all()
        )->toBe([$this->existing->id]);
});

it('applies the same half-open rule to blocked dates', function () {
    BlockedDate::factory()->create([
        'starts_at' => Carbon::parse('2026-12-20 00:00'),
        'ends_at' => Carbon::parse('2026-12-21 00:00'),
    ]);

    expect(BlockedDate::query()->overlapping(Carbon::parse('2026-12-21 00:00'), Carbon::parse('2026-12-21 17:00'))->count())->toBe(0)
        ->and(BlockedDate::query()->overlapping(Carbon::parse('2026-12-19 19:00'), Carbon::parse('2026-12-20 05:00'))->count())->toBe(1);
});
