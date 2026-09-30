<?php

use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\Faq;
use App\Models\Package;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OwnerSeeder;

/*
| M1: DatabaseSeeder produces the reference data from PLAN.md and is safe to re-run.
*/

beforeEach(function () {
    config([
        'wonderpool.owner.name' => 'Test Owner',
        'wonderpool.owner.email' => 'owner@example.com',
        'wonderpool.owner.password' => 'secret-password',
    ]);
});

it('seeds packages, amenities, settings, faqs and the owner', function () {
    $this->seed(DatabaseSeeder::class);

    $night = Package::query()->where('code', 'NIGHT-D')->firstOrFail();

    expect(Package::query()->pluck('base_price_cents', 'code')->all())
        ->toBe(['DAY-A' => 700000, 'NIGHT-D' => 900000, '24H' => 1500000])
        ->and($night->crosses_midnight)->toBeTrue()
        ->and($night->start_time)->toStartWith('19:00')
        ->and($night->end_time)->toStartWith('05:00')
        ->and($night->max_pax)->toBe(50)
        ->and(Amenity::query()->count())->toBeGreaterThanOrEqual(7)
        ->and(Faq::query()->count())->toBeGreaterThanOrEqual(3)
        ->and(Setting::query()->where('key', 'booking.downpayment_percent')->value('value'))->toBe('50')
        ->and(User::query()->where('email', 'owner@example.com')->first()?->role)->toBe(UserRole::Owner);
});

it('is idempotent', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Package::query()->count())->toBe(3)
        ->and(User::query()->count())->toBe(1);
});

it('keeps admin-edited settings when re-seeding', function () {
    $this->seed(DatabaseSeeder::class);
    Setting::query()->where('key', 'booking.downpayment_percent')->update(['value' => '30']);

    $this->seed(DatabaseSeeder::class);

    expect(Setting::query()->where('key', 'booking.downpayment_percent')->value('value'))->toBe('30');
});

it('skips the owner when credentials are missing from the environment', function () {
    config(['wonderpool.owner.password' => null]);

    $this->seed(OwnerSeeder::class);

    expect(User::query()->count())->toBe(0);
});
