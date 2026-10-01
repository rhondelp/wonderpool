<?php

/*
| M2: role restrictions. Staff = bookings only, so owner modules return 403 and are hidden from the nav.
*/

use App\Models\User;

it('blocks staff from owner-only pages', function (string $method, string $uri) {
    $staff = User::factory()->create();

    $this->actingAs($staff)->call($method, $uri)->assertForbidden();
})->with([
    ['GET', '/admin/settings/general'],
    ['PUT', '/admin/settings/general'],
    ['GET', '/admin/users'],
    ['GET', '/admin/users/create'],
    ['POST', '/admin/users'],
]);

it('lets staff reach the dashboard without owner links or revenue', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)->get('/admin')
        ->assertOk()
        ->assertSee('Pending approval')
        ->assertDontSee('Revenue this month')
        ->assertDontSee(route('admin.settings.index'))
        ->assertDontSee(route('admin.users.index'));
});

it('shows owner links and revenue to owners', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->get('/admin')
        ->assertOk()
        ->assertSee('Revenue this month')
        ->assertSee(route('admin.settings.index'))
        ->assertSee(route('admin.users.index'));
});
