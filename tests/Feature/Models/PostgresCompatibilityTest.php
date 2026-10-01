<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\PricingRule;
use App\Models\User;
use Database\Seeders\OwnerSeeder;
use Illuminate\Support\Facades\DB;

/*
| M0.1: PostgreSQL specifics — emails stored lowercase (comparisons are case-sensitive)
| and jsonb columns round-trip through the array casts.
*/

it('stores user emails trimmed and lowercase', function () {
    $user = User::factory()->create(['email' => '  Owner.Name@Example.COM ']);

    expect($user->fresh()->email)->toBe('owner.name@example.com')
        ->and(User::query()->where('email', 'owner.name@example.com')->exists())->toBeTrue();
});

it('stores booking guest emails lowercase and keeps null', function () {
    $booking = Booking::factory()->create(['guest_email' => 'Juan@Example.com']);
    $withoutEmail = Booking::factory()->create(['guest_email' => null]);

    expect($booking->fresh()->guest_email)->toBe('juan@example.com')
        ->and($withoutEmail->fresh()->guest_email)->toBeNull();
});

it('seeds the owner with a lowercase email and stays idempotent', function () {
    config([
        'wonderpool.owner.name' => 'Test Owner',
        'wonderpool.owner.email' => 'Owner@Example.com',
        'wonderpool.owner.password' => 'secret-password',
    ]);

    $this->seed(OwnerSeeder::class);
    $this->seed(OwnerSeeder::class);

    expect(User::query()->pluck('email')->all())->toBe(['owner@example.com']);
});

it('round-trips jsonb columns through array casts', function () {
    $rule = PricingRule::factory()->create(['days_of_week' => [5, 6, 7]]);
    $log = ActivityLog::factory()->create(['properties' => ['from' => 'pending', 'to' => 'approved']]);

    expect($rule->fresh()->days_of_week)->toBe([5, 6, 7])
        // jsonb does not preserve object key order, so compare loosely.
        ->and($log->fresh()->properties)->toEqual(['from' => 'pending', 'to' => 'approved']);
});

it('uses jsonb column types on PostgreSQL', function () {
    $types = collect(DB::select(
        "select table_name, data_type from information_schema.columns
         where table_schema = 'public' and column_name in ('days_of_week', 'properties')"
    ))->pluck('data_type', 'table_name')->all();

    expect($types)->toEqual(['activity_logs' => 'jsonb', 'pricing_rules' => 'jsonb']);
});
