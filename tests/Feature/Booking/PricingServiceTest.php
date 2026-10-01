<?php

/*
| M4: PricingService — rules stacked in priority order on the running package price (D-022).
| 2027-01-02 is a Saturday, 2027-01-04 a Monday.
*/

use App\Enums\PricingAdjustmentType;
use App\Exceptions\Booking\GuestCountExceededException;
use App\Exceptions\Booking\InvalidAddOnException;
use App\Models\AddOn;
use App\Models\PricingRule;
use App\Models\Setting;
use App\Services\Booking\PricingService;
use App\Services\SettingService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->pricing = app(PricingService::class);
    $this->day = dayPackage();
});

/**
 * Pricing rule shortcut.
 *
 * @param  array<string, mixed>  $attributes
 */
function rule(array $attributes): PricingRule
{
    return PricingRule::factory()->create($attributes + ['days_of_week' => null, 'starts_on' => null, 'ends_on' => null]);
}

it('returns the base price when no rules apply', function () {
    $quote = $this->pricing->quote($this->day, Carbon::parse('2027-01-04'), [], 20);

    expect($quote->basePriceCents)->toBe(700_000)
        ->and($quote->adjustments)->toBe([])
        ->and($quote->totalCents)->toBe(700_000)
        ->and($quote->downpaymentPercent)->toBe(50)
        ->and($quote->downpaymentRequiredCents)->toBe(350_000)
        ->and($quote->date)->toBe('2027-01-04');
});

it('applies a weekend rule only on the listed weekdays', function () {
    rule(['name' => 'Weekend rate', 'days_of_week' => [6, 7], 'adjustment_type' => PricingAdjustmentType::Percent, 'adjustment_value' => 10]);

    expect($this->pricing->quote($this->day, Carbon::parse('2027-01-02'))->totalCents)->toBe(770_000)  // Saturday
        ->and($this->pricing->quote($this->day, Carbon::parse('2027-01-03'))->totalCents)->toBe(770_000) // Sunday
        ->and($this->pricing->quote($this->day, Carbon::parse('2027-01-04'))->totalCents)->toBe(700_000); // Monday
});

it('applies season rules inclusively on both range edges', function () {
    rule(['name' => 'Summer', 'type' => 'season', 'starts_on' => '2027-03-01', 'ends_on' => '2027-05-31', 'adjustment_type' => PricingAdjustmentType::Fixed, 'adjustment_value' => 100_000]);

    expect($this->pricing->quote($this->day, Carbon::parse('2027-02-28'))->totalCents)->toBe(700_000)
        ->and($this->pricing->quote($this->day, Carbon::parse('2027-03-01'))->totalCents)->toBe(800_000)
        ->and($this->pricing->quote($this->day, Carbon::parse('2027-05-31'))->totalCents)->toBe(800_000)
        ->and($this->pricing->quote($this->day, Carbon::parse('2027-06-01'))->totalCents)->toBe(700_000);
});

it('applies a single-day holiday rule', function () {
    rule(['name' => 'Holy Week', 'type' => 'holiday', 'starts_on' => '2027-03-25', 'ends_on' => '2027-03-25', 'adjustment_value' => 20]);

    expect($this->pricing->quote($this->day, Carbon::parse('2027-03-25'))->totalCents)->toBe(840_000)
        ->and($this->pricing->quote($this->day, Carbon::parse('2027-03-26'))->totalCents)->toBe(700_000);
});

it('applies global rules and only this package\'s specific rules', function () {
    $night = nightPackage();
    rule(['name' => 'Global', 'adjustment_type' => PricingAdjustmentType::Fixed, 'adjustment_value' => 10_000]);
    rule(['name' => 'Night only', 'package_id' => $night->id, 'adjustment_type' => PricingAdjustmentType::Fixed, 'adjustment_value' => 50_000]);

    expect($this->pricing->quote($this->day, Carbon::parse('2027-01-04'))->totalCents)->toBe(710_000)
        ->and($this->pricing->quote($night, Carbon::parse('2027-01-04'))->totalCents)->toBe(960_000);
});

it('ignores inactive rules', function () {
    rule(['adjustment_value' => 50, 'is_active' => false]);

    expect($this->pricing->quote($this->day, Carbon::parse('2027-01-04'))->totalCents)->toBe(700_000);
});

it('stacks matching rules in priority order on the running price (percentages compound)', function () {
    // Inserted out of order on purpose: application order is priority asc, then id.
    rule(['name' => 'Summer', 'priority' => 20, 'adjustment_type' => PricingAdjustmentType::Fixed, 'adjustment_value' => 100_000]);
    rule(['name' => 'Weekend', 'priority' => 10, 'days_of_week' => [6, 7], 'adjustment_value' => 10]);
    rule(['name' => 'Promo', 'priority' => 30, 'adjustment_value' => -5]);

    $quote = $this->pricing->quote($this->day, Carbon::parse('2027-01-02'), [], 10);

    // 7,000 → +10% = 7,700 → +1,000 = 8,700 → -5% = 8,265
    expect(array_column($quote->adjustments, 'label'))->toBe(['Weekend (+10%)', 'Summer (+₱1,000.00)', 'Promo (-5%)'])
        ->and(array_column($quote->adjustments, 'delta_cents'))->toBe([70_000, 100_000, -43_500])
        ->and(array_column($quote->adjustments, 'running_cents'))->toBe([770_000, 870_000, 826_500])
        ->and($quote->packageSubtotalCents)->toBe(826_500)
        ->and($quote->totalCents)->toBe(826_500);
});

it('breaks priority ties by rule id', function () {
    $first = rule(['name' => 'A', 'priority' => 5, 'adjustment_type' => PricingAdjustmentType::Fixed, 'adjustment_value' => 1]);
    $second = rule(['name' => 'B', 'priority' => 5, 'adjustment_type' => PricingAdjustmentType::Fixed, 'adjustment_value' => 2]);

    expect(array_column($this->pricing->quote($this->day, Carbon::parse('2027-01-04'))->adjustments, 'rule_id'))->toBe([$first->id, $second->id]);
});

it('rounds percentages half away from zero to the centavo', function (int $cents, int $percent, int $expected) {
    expect(PricingService::percentOf($cents, $percent))->toBe($expected);
})->with([
    'exact' => [700_000, 10, 70_000],
    'half up' => [1_005, 10, 101],      // 100.5 → 101
    'below half' => [1_004, 10, 100],   // 100.4 → 100
    'negative half' => [1_005, -10, -101],
    'zero' => [0, 25, 0],
]);

it('never lets the package price go below zero', function () {
    rule(['adjustment_type' => PricingAdjustmentType::Fixed, 'adjustment_value' => -900_000]);

    expect($this->pricing->quote($this->day, Carbon::parse('2027-01-04'))->packageSubtotalCents)->toBe(0);
});

it('adds add-ons at their current price without applying rules to them', function () {
    rule(['adjustment_value' => 10]);
    $videoke = AddOn::factory()->create(['name' => 'Videoke', 'price_cents' => 50_000]);
    $hour = AddOn::factory()->create(['name' => 'Extra hour', 'price_cents' => 75_050]);

    $quote = $this->pricing->quote($this->day, Carbon::parse('2027-01-04'), [$videoke->id => 1, (string) $hour->id => '2']);

    expect($quote->addOns)->toBe([
        ['add_on_id' => $videoke->id, 'name' => 'Videoke', 'quantity' => 1, 'unit_price_cents' => 50_000, 'line_total_cents' => 50_000],
        ['add_on_id' => $hour->id, 'name' => 'Extra hour', 'quantity' => 2, 'unit_price_cents' => 75_050, 'line_total_cents' => 150_100],
    ])
        ->and($quote->addOnsTotalCents)->toBe(200_100)
        ->and($quote->totalCents)->toBe(770_000 + 200_100)
        ->and($quote->downpaymentRequiredCents)->toBe(485_050);
});

it('rounds the downpayment half up and follows the setting', function () {
    Setting::query()->create(['key' => 'booking.downpayment_percent', 'value' => '30', 'group' => 'booking']);
    app(SettingService::class)->flush();
    $odd = dayPackage(['code' => 'ODD', 'base_price_cents' => 100_005]);

    $quote = $this->pricing->quote($odd, Carbon::parse('2027-01-04'));

    expect($quote->downpaymentPercent)->toBe(30)
        ->and($quote->downpaymentRequiredCents)->toBe(30_002); // 30,001.5 → 30,002
});

it('enforces the package guest limit', function (int $guests) {
    $this->pricing->quote($this->day, Carbon::parse('2027-01-04'), [], $guests);
})->with([0, 51])->throws(GuestCountExceededException::class);

it('rejects unknown, inactive or badly quantified add-ons', function (Closure $addOns) {
    $this->pricing->quote($this->day, Carbon::parse('2027-01-04'), $addOns());
})->with([
    'unknown id' => fn () => [999_999 => 1],
    'inactive' => fn () => [AddOn::factory()->inactive()->create()->id => 1],
    'zero quantity' => fn () => [AddOn::factory()->create()->id => 0],
    'too many' => fn () => [AddOn::factory()->create()->id => 100],
])->throws(InvalidAddOnException::class);

it('exposes an array form for JSON quotes', function () {
    $array = $this->pricing->quote($this->day, Carbon::parse('2027-01-04'))->toArray();

    expect($array)->toMatchArray(['total_cents' => 700_000, 'total_formatted' => '₱7,000.00', 'downpayment_formatted' => '₱3,500.00']);
});
