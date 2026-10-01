<?php

/*
| M4: owner-only pricing rules CRUD, sample previews and the quote checker.
*/

use App\Enums\PricingAdjustmentType;
use App\Enums\PricingRuleType;
use App\Models\PricingRule;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->owner()->create());
    $this->day = dayPackage();
});

/**
 * Valid pricing rule form input.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ruleInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'Weekend rate',
        'package_id' => '',
        'type' => 'weekend',
        'starts_on' => '',
        'ends_on' => '',
        'days_of_week' => ['7', '6'],
        'adjustment_type' => 'percent',
        'adjustment_value' => '10',
        'priority' => '10',
        'is_active' => '1',
    ], $overrides);
}

it('creates a global weekend percent rule', function () {
    $this->post('/admin/pricing-rules', ruleInput())->assertRedirect('/admin/pricing-rules');

    $rule = PricingRule::query()->sole();
    expect($rule->package_id)->toBeNull()
        ->and($rule->type)->toBe(PricingRuleType::Weekend)
        ->and($rule->days_of_week)->toBe([6, 7])
        ->and($rule->adjustment_value)->toBe(10);
});

it('stores fixed amounts entered in pesos as signed centavos', function () {
    $this->post('/admin/pricing-rules', ruleInput([
        'name' => 'Summer', 'type' => 'season', 'starts_on' => '2027-03-01', 'ends_on' => '2027-05-31', 'days_of_week' => [],
        'adjustment_type' => 'fixed', 'adjustment_value' => '-500.50', 'package_id' => (string) $this->day->id,
    ]))->assertSessionHasNoErrors();

    $rule = PricingRule::query()->sole();
    expect($rule->adjustment_type)->toBe(PricingAdjustmentType::Fixed)
        ->and($rule->adjustment_value)->toBe(-50_050)
        ->and($rule->days_of_week)->toBeNull()
        ->and($rule->package_id)->toBe($this->day->id);
});

it('defaults a holiday end date to its start date', function () {
    $this->post('/admin/pricing-rules', ruleInput(['type' => 'holiday', 'starts_on' => '2027-12-25', 'days_of_week' => []]))->assertSessionHasNoErrors();

    expect(PricingRule::query()->sole()->ends_on->format('Y-m-d'))->toBe('2027-12-25');
});

it('validates pricing rules', function (array $overrides, string $field) {
    $this->post('/admin/pricing-rules', ruleInput($overrides))->assertSessionHasErrors($field);
})->with([
    'weekend without days' => [['days_of_week' => []], 'days_of_week'],
    'bad weekday' => [['days_of_week' => ['8']], 'days_of_week.0'],
    'season without dates' => [['type' => 'season', 'days_of_week' => []], 'starts_on'],
    'season end before start' => [['type' => 'season', 'starts_on' => '2027-05-01', 'ends_on' => '2027-04-01'], 'ends_on'],
    'percent too low' => [['adjustment_value' => '-101'], 'adjustment_value'],
    'percent with decimals' => [['adjustment_value' => '10.5'], 'adjustment_value'],
    'fixed with 3 decimals' => [['adjustment_type' => 'fixed', 'adjustment_value' => '1.234'], 'adjustment_value'],
    'unknown package' => [['package_id' => '999999'], 'package_id'],
    'unknown type' => [['type' => 'birthday'], 'type'],
]);

it('shows each rule with a sample effect in application order', function () {
    PricingRule::factory()->create(['name' => 'Late rule', 'priority' => 50, 'adjustment_value' => 20]);
    PricingRule::factory()->create(['name' => 'Early rule', 'priority' => 5, 'adjustment_value' => 10]);

    $this->get('/admin/pricing-rules')->assertOk()
        ->assertSeeInOrder(['Early rule (+10%)', '₱7,000.00', '₱7,700.00', 'Late rule (+20%)', '₱8,400.00']);
});

it('checks a full quote for a package and date', function () {
    PricingRule::factory()->create(['name' => 'Weekend', 'days_of_week' => [6, 7], 'adjustment_value' => 10]);

    $this->get("/admin/pricing-rules?quote_package={$this->day->id}&quote_date=2027-01-02")->assertOk()
        ->assertSee('Weekend (+10%)')->assertSee('₱7,700.00')->assertSee('₱3,850.00');

    $this->get("/admin/pricing-rules?quote_package={$this->day->id}&quote_date=2027-01-04")->assertOk()
        ->assertSee('No rules apply on Monday, Jan 4, 2027');
});

it('renders the form with the live preview data and edits a rule', function () {
    $rule = PricingRule::factory()->create(['adjustment_type' => PricingAdjustmentType::Fixed, 'adjustment_value' => 100_000, 'days_of_week' => [6, 7]]);

    $this->get('/admin/pricing-rules/create')->assertOk()->assertSee('Sample price')->assertSee('Day Package (A)');
    $this->get("/admin/pricing-rules/{$rule->id}/edit")->assertOk()->assertSee('value="1000.00"', false);

    $this->put("/admin/pricing-rules/{$rule->id}", ruleInput(['name' => 'Weekend v2', 'adjustment_value' => '15']))->assertRedirect('/admin/pricing-rules');
    expect($rule->fresh()->only(['name', 'adjustment_value']))->toBe(['name' => 'Weekend v2', 'adjustment_value' => 15]);

    $this->delete("/admin/pricing-rules/{$rule->id}")->assertRedirect('/admin/pricing-rules');
    expect(PricingRule::query()->count())->toBe(0);
});

it('is owner only', function () {
    $this->actingAs(User::factory()->create())->get('/admin/pricing-rules')->assertForbidden();
    $this->actingAs(User::factory()->create())->post('/admin/pricing-rules', ruleInput())->assertForbidden();
});
