<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Feature tests extend Tests\TestCase and run against the PostgreSQL
| test database wonderpool_test (phpunit.xml) via RefreshDatabase. Unit tests are plain PHP.
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Booking-engine helpers (M4)
|--------------------------------------------------------------------------
| The three PLAN.md §1 packages with their real hours.
*/

/**
 * Day package 07:00–17:00, ₱7,000, max 50.
 *
 * @param  array<string, mixed>  $attributes
 */
function dayPackage(array $attributes = []): App\Models\Package
{
    return App\Models\Package::factory()->create($attributes + [
        'name' => 'Day Package (A)', 'code' => 'DAY-A', 'base_price_cents' => 700_000,
        'start_time' => '07:00:00', 'end_time' => '17:00:00', 'crosses_midnight' => false, 'max_pax' => 50,
    ]);
}

/**
 * Night package 19:00–05:00 next day, ₱9,000, max 50.
 *
 * @param  array<string, mixed>  $attributes
 */
function nightPackage(array $attributes = []): App\Models\Package
{
    return App\Models\Package::factory()->create($attributes + [
        'name' => 'Night Package (D)', 'code' => 'NIGHT-D', 'base_price_cents' => 900_000,
        'start_time' => '19:00:00', 'end_time' => '05:00:00', 'crosses_midnight' => true, 'max_pax' => 50,
    ]);
}

/**
 * 24-Hour package 07:00–05:00 next day, ₱15,000, max 50.
 *
 * @param  array<string, mixed>  $attributes
 */
function fullDayPackage(array $attributes = []): App\Models\Package
{
    return App\Models\Package::factory()->create($attributes + [
        'name' => '24-Hour Package', 'code' => '24H', 'base_price_cents' => 1_500_000,
        'start_time' => '07:00:00', 'end_time' => '05:00:00', 'crosses_midnight' => true, 'max_pax' => 50,
    ]);
}

/**
 * Valid BookingService::create() input.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bookingData(App\Models\Package $package, string $date, array $overrides = []): array
{
    return array_merge([
        'package_id' => $package->id,
        'date' => $date,
        'guest_name' => 'Juan Dela Cruz',
        'guest_phone' => '+639171234567',
        'guest_email' => 'Juan@Example.com',
        'guest_count' => 30,
    ], $overrides);
}
