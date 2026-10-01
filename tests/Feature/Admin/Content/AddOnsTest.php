<?php

/*
| M3: add-on CRUD, search and the "used on bookings" delete guard.
*/

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\AddOn;
use App\Models\BookingAddOn;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->owner()->create());
});

it('lists, searches and filters add-ons', function () {
    AddOn::factory()->create(['name' => 'Videoke Set']);
    AddOn::factory()->inactive()->create(['name' => 'Extra Hour']);

    $this->get('/admin/add-ons')->assertOk()->assertSee('Videoke Set')->assertSee('Extra Hour');
    $this->get('/admin/add-ons?q=VIDEO')->assertSee('Videoke Set')->assertDontSee('Extra Hour');
    $this->get('/admin/add-ons?status=active')->assertSee('Videoke Set')->assertDontSee('Extra Hour');
});

it('creates an add-on with the price in centavos', function () {
    $this->post('/admin/add-ons', ['name' => 'Extra Pax', 'description' => 'Per head', 'price' => '150', 'is_active' => '1'])
        ->assertRedirect('/admin/add-ons');

    $addOn = AddOn::query()->where('name', 'Extra Pax')->sole();
    expect($addOn->price_cents)->toBe(15_000)
        ->and($addOn->is_active)->toBeTrue()
        ->and(ActivityLog::query()->where('action', ActivityAction::ContentCreated->value)->exists())->toBeTrue();
});

it('validates add-on input', function (array $input, string $field) {
    $this->post('/admin/add-ons', $input + ['name' => 'X', 'price' => '10'])->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'price not numeric' => [['price' => 'free'], 'price'],
    'negative price' => [['price' => '-5'], 'price'],
]);

it('updates an add-on (unchecked box = inactive)', function () {
    $addOn = AddOn::factory()->create();

    $this->put("/admin/add-ons/{$addOn->id}", ['name' => 'Cottage Rental', 'price' => '500.25', 'is_active' => '0'])
        ->assertRedirect('/admin/add-ons');

    $addOn->refresh();
    expect($addOn->name)->toBe('Cottage Rental')
        ->and($addOn->price_cents)->toBe(50_025)
        ->and($addOn->is_active)->toBeFalse();
});

it('deletes an unused add-on', function () {
    $addOn = AddOn::factory()->create();

    $this->delete("/admin/add-ons/{$addOn->id}")->assertRedirect('/admin/add-ons');

    expect(AddOn::query()->find($addOn->id))->toBeNull();
});

it('refuses to delete an add-on used on a booking', function () {
    $addOn = AddOn::factory()->create(['name' => 'Videoke']);
    BookingAddOn::factory()->create(['add_on_id' => $addOn->id]);

    $this->from('/admin/add-ons')->delete("/admin/add-ons/{$addOn->id}")
        ->assertSessionHas('warning', fn (string $m): bool => str_contains($m, 'Videoke is used on 1 booking(s)'));

    expect(AddOn::query()->find($addOn->id))->not->toBeNull();
});
