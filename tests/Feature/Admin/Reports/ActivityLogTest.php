<?php

/*
| M7: owner activity log (D-036) — listing, filters, search, subject links, access.
*/

use App\Enums\ActivityAction;
use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create(['name' => 'Olivia Owner']);
    $this->staff = User::factory()->create(['name' => 'Sam Staff']);
    $this->booking = Booking::factory()->create(['reference_code' => 'WP-2610-QRST']);
    $this->package = dayPackage();

    $logger = app(ActivityLogger::class);
    Carbon::setTestNow('2026-10-01 09:00');
    $logger->log(ActivityAction::Login, $this->staff, [], $this->staff);
    Carbon::setTestNow('2026-10-02 09:00');
    $logger->log(ActivityAction::BookingStatusChanged, $this->booking, ['from' => 'pending', 'to' => 'approved'], $this->staff);
    $logger->log(ActivityAction::ContentUpdated, $this->package, ['type' => 'package', 'label' => 'Day Package (A)', 'changed' => ['base_price_cents']], $this->owner);
    $logger->log(ActivityAction::BookingExpired, $this->booking, [], null);
    Carbon::setTestNow();
});

it('lists entries newest first with actor, action, subject link and details', function () {
    $this->actingAs($this->owner)->get('/admin/activity-log')->assertOk()
        ->assertViewHas('entries', fn ($entries) => $entries->pluck('action')->all() === ['booking.expired', 'content.updated', 'booking.status_changed', 'auth.login'])
        ->assertSee('Guest / system')
        ->assertSee(route('admin.bookings.show', $this->booking))
        ->assertSee(route('admin.packages.edit', $this->package))
        ->assertSee('WP-2610-QRST')
        ->assertSee('[&quot;base_price_cents&quot;]', false);
});

/**
 * Actions listed for a query string (the filter dropdowns repeat every label, so read view data).
 *
 * @return list<string>
 */
function loggedActions(string $query): array
{
    return test()->get('/admin/activity-log?'.$query)->assertOk()->viewData('entries')->pluck('action')->all();
}

it('filters by actor, area, action and dates', function () {
    $this->actingAs($this->owner);

    expect(loggedActions("user={$this->staff->id}"))->toBe(['booking.status_changed', 'auth.login'])
        ->and(loggedActions('user=system'))->toBe(['booking.expired'])
        ->and(loggedActions('module=booking'))->toBe(['booking.expired', 'booking.status_changed'])
        ->and(loggedActions('action=content.updated'))->toBe(['content.updated'])
        ->and(loggedActions('from=2026-10-01&to=2026-10-01'))->toBe(['auth.login']);
});

it('searches booking references and details case-insensitively', function () {
    $this->actingAs($this->owner);

    expect(loggedActions('q=wp-2610-qrst'))->toBe(['booking.expired', 'booking.status_changed'])
        ->and(loggedActions('q=DAY%20PACKAGE'))->toBe(['content.updated'])
        ->and(loggedActions('q=100%25'))->toBe([]);
});

it('validates filters', function (string $query) {
    $this->actingAs($this->owner)->get('/admin/activity-log?'.$query)->assertSessionHasErrors();
})->with(['user=abc', 'module=nope', 'action=booking.deleted', 'from=2026-10-05&to=2026-10-01']);

it('is owner only', function () {
    $this->actingAs($this->staff)->get('/admin/activity-log')->assertForbidden();
    $this->actingAs($this->staff)->get('/admin')->assertDontSee(route('admin.activity-log'));
    $this->actingAs($this->owner)->get('/admin')->assertSee(route('admin.activity-log'));
});
