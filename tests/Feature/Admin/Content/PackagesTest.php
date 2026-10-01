<?php

/*
| M3: package CRUD, time coherence, delete guard, search/filter and reorder.
*/

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create();
    $this->actingAs($this->owner);
});

/**
 * Valid form input, overridable per test.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function packageInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sunset Package',
        'code' => 'sun-1',
        'description' => 'Afternoon to evening.',
        'base_price' => '8500.50',
        'start_time' => '13:00',
        'end_time' => '21:00',
        'crosses_midnight' => '0',
        'max_pax' => '40',
        'is_active' => '1',
        'sort_order' => '',
    ], $overrides);
}

it('lists packages with case-insensitive search and status filter', function () {
    Package::factory()->create(['name' => 'Day Package', 'code' => 'DAY-1']);
    Package::factory()->inactive()->create(['name' => 'Night Package', 'code' => 'NIGHT-1']);

    $this->get('/admin/packages')->assertOk()->assertSee('Day Package')->assertSee('Night Package');
    $this->get('/admin/packages?q=nIgHt')->assertOk()->assertSee('Night Package')->assertDontSee('Day Package');
    $this->get('/admin/packages?q=day-1')->assertOk()->assertSee('Day Package');
    $this->get('/admin/packages?status=inactive')->assertOk()->assertSee('Night Package')->assertDontSee('Day Package');
});

it('treats LIKE wildcards in the search as literal text', function () {
    Package::factory()->create(['name' => 'Promo 50% off']);
    Package::factory()->create(['name' => 'Regular']);

    $this->get('/admin/packages?q=%25')->assertSee('Promo 50% off')->assertDontSee('Regular');
});

it('shows an empty state when nothing matches', function () {
    $this->get('/admin/packages?q=nothing-here')->assertOk()->assertSee('No packages found');
});

it('creates a package in centavos with normalized code and times, placed last', function () {
    Package::factory()->create(['sort_order' => 7]);

    $this->post('/admin/packages', packageInput())->assertRedirect('/admin/packages')->assertSessionHas('success');

    $package = Package::query()->where('code', 'SUN-1')->sole();
    expect($package->base_price_cents)->toBe(850_050)
        ->and($package->start_time)->toBe('13:00:00')
        ->and($package->end_time)->toBe('21:00:00')
        ->and($package->crosses_midnight)->toBeFalse()
        ->and($package->sort_order)->toBe(8)
        ->and(ActivityLog::query()->where('action', ActivityAction::ContentCreated->value)->where('subject_id', $package->id)->exists())->toBeTrue();
});

it('accepts an overnight package that ends the next day', function () {
    $this->post('/admin/packages', packageInput(['code' => 'NIGHT-X', 'start_time' => '19:00', 'end_time' => '05:00', 'crosses_midnight' => '1']))
        ->assertSessionHasNoErrors();

    expect(Package::query()->where('code', 'NIGHT-X')->sole()->crosses_midnight)->toBeTrue();
});

it('validates package input', function (array $overrides, string $field) {
    Package::factory()->create(['code' => 'TAKEN']);

    $this->from('/admin/packages/create')->post('/admin/packages', packageInput($overrides))->assertSessionHasErrors($field);
})->with([
    'same-day end before start' => [['start_time' => '17:00', 'end_time' => '07:00'], 'end_time'],
    'same-day equal times' => [['start_time' => '07:00', 'end_time' => '07:00'], 'end_time'],
    'overnight end after start' => [['start_time' => '07:00', 'end_time' => '17:00', 'crosses_midnight' => '1'], 'end_time'],
    'bad time format' => [['start_time' => '7am'], 'start_time'],
    'duplicate code (any case)' => [['code' => 'taken'], 'code'],
    'code with spaces' => [['code' => 'DAY A'], 'code'],
    'negative price' => [['base_price' => '-1'], 'base_price'],
    'three decimals' => [['base_price' => '10.555'], 'base_price'],
    'zero pax' => [['max_pax' => '0'], 'max_pax'],
    'missing name' => [['name' => ''], 'name'],
]);

it('updates a package and logs the changed fields', function () {
    $package = Package::factory()->create(['code' => 'DAY-A', 'base_price_cents' => 700_000]);

    $this->put("/admin/packages/{$package->id}", packageInput(['name' => 'Day Package (A)', 'code' => 'DAY-A', 'base_price' => '7500']))
        ->assertRedirect('/admin/packages');

    $package->refresh();
    expect($package->name)->toBe('Day Package (A)')
        ->and($package->base_price_cents)->toBe(750_000);

    $log = ActivityLog::query()->where('action', ActivityAction::ContentUpdated->value)->sole();
    expect($log->properties['type'])->toBe('Package')
        ->and($log->properties['fields'])->toContain('name', 'base_price_cents');
});

it('keeps its own code when updating (unique ignores itself)', function () {
    $package = Package::factory()->create(['code' => 'KEEP']);

    $this->put("/admin/packages/{$package->id}", packageInput(['code' => 'keep']))->assertSessionHasNoErrors();
});

it('deletes a package without bookings', function () {
    $package = Package::factory()->create();

    $this->delete("/admin/packages/{$package->id}")->assertRedirect('/admin/packages')->assertSessionHas('success');

    expect(Package::query()->find($package->id))->toBeNull()
        ->and(ActivityLog::query()->where('action', ActivityAction::ContentDeleted->value)->exists())->toBeTrue();
});

it('refuses to delete a package with bookings, even soft-deleted ones', function (bool $trashed) {
    $package = Package::factory()->create(['name' => 'Busy Package']);
    $booking = Booking::factory()->for($package)->create();
    if ($trashed) {
        $booking->delete();
    }

    $this->from('/admin/packages')->delete("/admin/packages/{$package->id}")
        ->assertRedirect('/admin/packages')
        ->assertSessionHas('warning', fn (string $message): bool => str_contains($message, 'Busy Package has 1 booking(s)') && str_contains($message, 'Deactivate'));

    expect(Package::query()->find($package->id))->not->toBeNull()
        ->and(ActivityLog::query()->where('action', ActivityAction::ContentDeleted->value)->exists())->toBeFalse();
})->with(['active booking' => false, 'soft-deleted booking' => true]);

it('reorders packages', function () {
    [$a, $b, $c] = Package::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create();

    $this->patchJson('/admin/packages/reorder', ['ids' => [$c->id, $a->id, $b->id]])
        ->assertOk()->assertJson(['message' => 'Order saved.']);

    expect(Package::query()->ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('renders the create and edit forms with current values', function () {
    $package = Package::factory()->overnight()->create(['base_price_cents' => 900_000]);

    $this->get('/admin/packages/create')->assertOk()->assertSee('Ends the next day');
    $this->get("/admin/packages/{$package->id}/edit")->assertOk()->assertSee('value="9000.00"', false)->assertSee('value="19:00"', false);
});
