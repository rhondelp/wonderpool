<?php

/*
| M2: owner settings screen (one tab per SettingGroup) and its validation.
*/

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Database\Seeders\SettingSeeder;

beforeEach(function () {
    $this->seed(SettingSeeder::class);
    $this->owner = User::factory()->owner()->create();
});

it('redirects /admin/settings to the general tab', function () {
    $this->actingAs($this->owner)->get('/admin/settings')->assertRedirect('/admin/settings/general');
});

it('renders every settings group with current values', function (string $group, string $expected) {
    $this->actingAs($this->owner)->get("/admin/settings/{$group}")->assertOk()->assertSee($expected);
})->with([
    ['general', 'Wonderpool Garden Resort'],
    ['booking', 'Downpayment (%)'],
    ['payment', 'GCash'],
    ['contact', 'info@example.com'],
    ['social', 'Facebook page'],
]);

it('returns 404 for an unknown group', function () {
    $this->actingAs($this->owner)->get('/admin/settings/unknown')->assertNotFound();
});

it('saves a group, refreshes the cache and logs the change', function () {
    $settings = app(SettingService::class);
    expect($settings->int('booking.downpayment_percent'))->toBe(50);

    $this->actingAs($this->owner)->put('/admin/settings/booking', ['booking' => [
        'downpayment_percent' => '30',
        'pending_hold_hours' => '24',
        'lead_time_hours' => '12',
        'max_advance_days' => '365',
    ]])->assertRedirect('/admin/settings/booking')->assertSessionHas('success', 'Booking rules settings saved.');

    expect($settings->int('booking.downpayment_percent'))->toBe(30)
        ->and($settings->int('booking.lead_time_hours'))->toBe(12);

    $log = ActivityLog::query()->where('action', ActivityAction::SettingsUpdated->value)->sole();
    expect($log->user_id)->toBe($this->owner->id)
        ->and(array_keys($log->properties['changes']))->toEqualCanonicalizing(['booking.downpayment_percent', 'booking.lead_time_hours'])
        ->and($log->properties['changes']['booking.downpayment_percent'])->toEqual(['from' => '50', 'to' => '30']);
});

it('ignores keys from other groups', function () {
    $this->actingAs($this->owner)->put('/admin/settings/general', [
        'general' => ['resort_name' => 'Wonderpool'],
        'booking' => ['downpayment_percent' => '1'],
    ])->assertSessionHasNoErrors();

    expect(Setting::query()->where('key', 'booking.downpayment_percent')->value('value'))->toBe('50');
});

it('stores cleared optional fields as empty strings', function () {
    $this->actingAs($this->owner)->put('/admin/settings/social', ['social' => [
        'facebook_url' => '',
        'instagram_url' => 'https://www.instagram.com/wonderpool',
    ]])->assertSessionHasNoErrors();

    expect(Setting::query()->where('key', 'social.facebook_url')->value('value'))->toBe('')
        ->and(Setting::query()->where('key', 'social.instagram_url')->value('value'))->toBe('https://www.instagram.com/wonderpool');
});

it('validates settings input', function (string $group, array $input, string $errorKey) {
    $this->actingAs($this->owner)
        ->from("/admin/settings/{$group}")
        ->put("/admin/settings/{$group}", [$group => $input])
        ->assertSessionHasErrors($errorKey);
})->with([
    'downpayment over 100' => ['booking', ['downpayment_percent' => '120', 'pending_hold_hours' => '24', 'lead_time_hours' => '24', 'max_advance_days' => '365'], 'booking.downpayment_percent'],
    'non-integer hours' => ['booking', ['downpayment_percent' => '50', 'pending_hold_hours' => 'abc', 'lead_time_hours' => '24', 'max_advance_days' => '365'], 'booking.pending_hold_hours'],
    'missing resort name' => ['general', ['resort_name' => ''], 'general.resort_name'],
    'bad contact email' => ['contact', ['phone' => '0917', 'email' => 'not-an-email', 'address' => 'Somewhere'], 'contact.email'],
    'http map url' => ['contact', ['phone' => '0917', 'email' => 'a@b.ph', 'address' => 'Somewhere', 'map_embed_url' => 'http://maps.example.com'], 'contact.map_embed_url'],
]);

it('shows validation errors next to the field', function () {
    $this->actingAs($this->owner)
        ->from('/admin/settings/general')
        ->followingRedirects()
        ->put('/admin/settings/general', ['general' => ['resort_name' => '']])
        ->assertSee('The resort name field is required.');
});
