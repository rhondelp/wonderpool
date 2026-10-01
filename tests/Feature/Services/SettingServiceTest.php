<?php

/*
| M2: SettingService caching, defaults and change detection.
*/

use App\Enums\SettingGroup;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Support\Facades\Cache;

it('falls back to declared defaults when a key is not stored', function () {
    $settings = app(SettingService::class);

    expect($settings->get('booking.downpayment_percent'))->toBe('50')
        ->and($settings->int('booking.max_advance_days'))->toBe(365)
        ->and($settings->get('unknown.key', 'fallback'))->toBe('fallback')
        ->and($settings->int('unknown.key', 7))->toBe(7);
});

it('caches all settings and serves reads from the cache', function () {
    Setting::factory()->create(['key' => 'general.resort_name', 'value' => 'Cached Name', 'group' => 'general']);
    $settings = app(SettingService::class);

    expect($settings->get('general.resort_name'))->toBe('Cached Name')
        ->and(Cache::has(SettingService::CACHE_KEY))->toBeTrue();

    // A direct DB write is invisible until the cache is flushed.
    Setting::query()->where('key', 'general.resort_name')->update(['value' => 'Changed']);
    expect($settings->get('general.resort_name'))->toBe('Cached Name');

    $settings->flush();
    expect($settings->get('general.resort_name'))->toBe('Changed');
});

it('returns only changed keys and skips logging when nothing changed', function () {
    $settings = app(SettingService::class);

    $changes = $settings->update(SettingGroup::General, ['general.resort_name' => '  Wonderpool Resort  ']);
    expect($changes)->toBe(['general.resort_name' => ['from' => 'Wonderpool Garden Resort', 'to' => 'Wonderpool Resort']]);

    expect($settings->update(SettingGroup::General, ['general.resort_name' => 'Wonderpool Resort']))->toBe([])
        ->and(ActivityLog::query()->count())->toBe(1);
});

it('lists a group in declaration order with defaults filled in', function () {
    expect(array_keys(app(SettingService::class)->group(SettingGroup::Booking)))->toBe([
        'booking.downpayment_percent',
        'booking.pending_hold_hours',
        'booking.lead_time_hours',
        'booking.max_advance_days',
    ]);
});
