<?php

/*
| M5: POST /book/quote and GET /book/availability return exactly what the M4 services compute.
| "Now" is 2027-01-01 09:00; 2027-01-02 is a Saturday.
*/

use App\Models\AddOn;
use App\Models\BlockedDate;
use App\Models\PricingRule;
use App\Services\Booking\BookingService;
use App\Services\Booking\PricingService;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 09:00'));
    $this->seed(SettingSeeder::class);
    $this->day = dayPackage();
});

afterEach(fn () => Carbon::setTestNow());

it('returns the PricingService breakdown and availability for a free date', function () {
    PricingRule::factory()->create(['name' => 'Weekend', 'days_of_week' => [6, 7], 'adjustment_value' => 10]);
    $addOn = AddOn::factory()->create(['price_cents' => 50_000]);
    $night = nightPackage();

    $response = $this->postJson('/book/quote', ['package_id' => $night->id, 'date' => '2027-01-09', 'guest_count' => 20, 'add_ons' => [$addOn->id => 2, 999 => 0]]);

    $expected = app(PricingService::class)->quote($night, Carbon::parse('2027-01-09'), [$addOn->id => 2], 20)->toArray();
    $response->assertOk()
        ->assertJson(['available' => true, 'reason' => null, 'breakdown' => $expected])
        ->assertJsonPath('breakdown.total_cents', 1_090_000)
        ->assertJsonPath('window.label', 'Sat, Jan 9, 2027, 7:00 PM – Sun, Jan 10, 5:00 AM');
});

it('reports a taken slot', function () {
    app(BookingService::class)->create(bookingData(fullDayPackage(), '2027-01-04'));

    $this->postJson('/book/quote', ['package_id' => $this->day->id, 'date' => '2027-01-04', 'guest_count' => 10])
        ->assertOk()
        ->assertJson(['available' => false])
        ->assertJsonPath('reason', 'Sorry, the resort is already booked or closed at that time. Please pick another date or package.');
});

it('reports dates outside the online booking window', function () {
    $this->postJson('/book/quote', ['package_id' => $this->day->id, 'date' => '2027-01-02', 'guest_count' => 10])
        ->assertJson(['available' => false])
        ->assertJsonPath('reason', fn (string $reason): bool => str_contains($reason, 'at least 24 hours'));
});

it('reports guest-count problems without a breakdown', function () {
    $this->postJson('/book/quote', ['package_id' => $this->day->id, 'date' => '2027-01-04', 'guest_count' => 60])
        ->assertJson(['available' => false, 'breakdown' => null])
        ->assertJsonPath('reason', 'Day Package (A) allows 1 to 50 guests; 60 requested.');
});

it('validates quote input', function (array $payload, string $field) {
    $this->postJson('/book/quote', $payload + ['package_id' => $this->day->id, 'date' => '2027-01-04', 'guest_count' => 10])
        ->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'missing date' => [['date' => ''], 'date'],
    'bad date' => [['date' => '2027/01/04'], 'date'],
    'zero guests' => [['guest_count' => 0], 'guest_count'],
    'unknown package' => [['package_id' => 999_999], 'package_id'],
    'negative add-on qty' => [['add_ons' => [1 => -1]], 'add_ons.1'],
]);

it('rejects inactive packages in quotes', function () {
    $inactive = dayPackage(['code' => 'OFF', 'is_active' => false]);

    $this->postJson('/book/quote', ['package_id' => $inactive->id, 'date' => '2027-01-04', 'guest_count' => 10])->assertJsonValidationErrors('package_id');
});

it('returns a month calendar with booked, blocked and closed days', function () {
    app(BookingService::class)->create(bookingData($this->day, '2027-01-04'));
    BlockedDate::factory()->create(['starts_at' => Carbon::parse('2027-01-06 00:00'), 'ends_at' => Carbon::parse('2027-01-07 00:00')]);

    $calendar = $this->getJson("/book/availability?package_id={$this->day->id}&month=2027-01")->assertOk()->json();

    expect($calendar['month'])->toBe('2027-01')
        ->and($calendar['label'])->toBe('January 2027')
        ->and($calendar['days'])->toHaveCount(31)
        ->and($calendar['days']['2027-01-01'])->toBe('closed')    // today
        ->and($calendar['days']['2027-01-02'])->toBe('closed')    // inside the 24 h lead time
        ->and($calendar['days']['2027-01-03'])->toBe('available')
        ->and($calendar['days']['2027-01-04'])->toBe('booked')
        ->and($calendar['days']['2027-01-06'])->toBe('booked')    // blocked date
        ->and($calendar['has_previous'])->toBeFalse()
        ->and($calendar['has_next'])->toBeTrue();
});

it('shows a Night still free on a date whose Day is booked', function () {
    app(BookingService::class)->create(bookingData($this->day, '2027-01-04'));
    $night = nightPackage();

    expect($this->getJson("/book/availability?package_id={$night->id}&month=2027-01")->json('days.2027-01-04'))->toBe('available');
});

it('validates calendar parameters', function () {
    $this->getJson('/book/availability?month=2027-01')->assertJsonValidationErrors('package_id');
    $this->getJson("/book/availability?package_id={$this->day->id}&month=January")->assertJsonValidationErrors('month');
    $this->getJson('/book/availability?package_id=999999')->assertNotFound();
});
