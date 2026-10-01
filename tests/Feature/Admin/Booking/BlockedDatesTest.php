<?php

/*
| M4: owner-only blocked dates CRUD with overlap warnings.
*/

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2027-03-01 09:00'));
    $this->owner = User::factory()->owner()->create();
    $this->actingAs($this->owner);
});

afterEach(fn () => Carbon::setTestNow());

it('blocks whole days from midnight to the midnight after the last day', function () {
    $this->post('/admin/blocked-dates', ['whole_days' => '1', 'start_date' => '2027-03-10', 'end_date' => '2027-03-12', 'reason' => 'Pool repainting'])
        ->assertRedirect('/admin/blocked-dates')->assertSessionHas('success')->assertSessionMissing('warning');

    $block = BlockedDate::query()->sole();
    expect($block->starts_at->format('Y-m-d H:i'))->toBe('2027-03-10 00:00')
        ->and($block->ends_at->format('Y-m-d H:i'))->toBe('2027-03-13 00:00')
        ->and($block->created_by)->toBe($this->owner->id)
        ->and($block->isWholeDays())->toBeTrue()
        ->and(ActivityLog::query()->where('action', ActivityAction::ContentCreated->value)->value('properties'))->toMatchArray(['type' => 'BlockedDate']);
});

it('blocks an exact time range', function () {
    $this->post('/admin/blocked-dates', ['whole_days' => '0', 'starts_at' => '2027-03-10T13:00', 'ends_at' => '2027-03-10T15:30', 'reason' => 'Electrical repair'])
        ->assertSessionHasNoErrors();

    $block = BlockedDate::query()->sole();
    expect($block->starts_at->format('Y-m-d H:i'))->toBe('2027-03-10 13:00')
        ->and($block->ends_at->format('Y-m-d H:i'))->toBe('2027-03-10 15:30')
        ->and($block->isWholeDays())->toBeFalse();
});

it('validates blocked dates', function (array $input, string $field) {
    $this->post('/admin/blocked-dates', $input + ['reason' => 'Maintenance'])->assertSessionHasErrors($field);
})->with([
    'last day before first' => [['whole_days' => '1', 'start_date' => '2027-03-12', 'end_date' => '2027-03-10'], 'end_date'],
    'missing first day' => [['whole_days' => '1', 'end_date' => '2027-03-10'], 'start_date'],
    'end before start' => [['whole_days' => '0', 'starts_at' => '2027-03-10T15:00', 'ends_at' => '2027-03-10T13:00'], 'ends_at'],
    'end equals start' => [['whole_days' => '0', 'starts_at' => '2027-03-10T15:00', 'ends_at' => '2027-03-10T15:00'], 'ends_at'],
    'missing reason' => [['whole_days' => '1', 'start_date' => '2027-03-10', 'end_date' => '2027-03-10', 'reason' => ''], 'reason'],
]);

it('warns about overlapping bookings without cancelling them', function () {
    $booking = Booking::factory()->window(Carbon::parse('2027-03-10 07:00'), Carbon::parse('2027-03-10 17:00'))->create();

    $this->post('/admin/blocked-dates', ['whole_days' => '1', 'start_date' => '2027-03-10', 'end_date' => '2027-03-10', 'reason' => 'Private event'])
        ->assertSessionHas('warning', fn (string $m): bool => str_contains($m, $booking->reference_code) && str_contains($m, 'NOT cancelled'));

    expect($booking->fresh()->status->value)->toBe('pending');
});

it('lists upcoming blocks by default, past on request, and searches reasons', function () {
    BlockedDate::factory()->create(['reason' => 'Upcoming repaint', 'starts_at' => Carbon::parse('2027-03-10'), 'ends_at' => Carbon::parse('2027-03-11')]);
    BlockedDate::factory()->create(['reason' => 'Old fiesta closure', 'starts_at' => Carbon::parse('2027-02-01'), 'ends_at' => Carbon::parse('2027-02-02')]);

    $this->get('/admin/blocked-dates')->assertOk()->assertSee('Upcoming repaint')->assertDontSee('Old fiesta closure');
    $this->get('/admin/blocked-dates?status=past')->assertSee('Old fiesta closure')->assertDontSee('Upcoming repaint');
    $this->get('/admin/blocked-dates?status=all&q=FIESTA')->assertSee('Old fiesta closure')->assertDontSee('Upcoming repaint');
});

it('edits and deletes a block', function () {
    $block = BlockedDate::factory()->create(['starts_at' => Carbon::parse('2027-03-10'), 'ends_at' => Carbon::parse('2027-03-11')]);

    $this->get("/admin/blocked-dates/{$block->id}/edit")->assertOk()->assertSee('value="2027-03-10"', false);

    $this->put("/admin/blocked-dates/{$block->id}", ['whole_days' => '1', 'start_date' => '2027-03-20', 'end_date' => '2027-03-21', 'reason' => 'Moved'])
        ->assertRedirect('/admin/blocked-dates');
    expect($block->fresh()->starts_at->format('Y-m-d'))->toBe('2027-03-20');

    $this->delete("/admin/blocked-dates/{$block->id}")->assertRedirect('/admin/blocked-dates');
    expect(BlockedDate::query()->count())->toBe(0);
});

it('is owner only', function () {
    $this->actingAs(User::factory()->create())->get('/admin/blocked-dates')->assertForbidden();
});
