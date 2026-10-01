<?php

/*
| M2: dashboard shell figures (DashboardService) and the upcoming list.
*/

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Support\Carbon;

it('counts pending, arrivals today, the next 7 days and verified revenue this month', function () {
    $now = Carbon::parse('2026-10-15 09:00');
    Carbon::setTestNow($now);

    Booking::factory()->window($now->copy()->setTime(13, 0), $now->copy()->setTime(17, 0))->create();          // pending, today
    Booking::factory()->approved()->window($now->copy()->addDays(3), $now->copy()->addDays(3)->addHours(8))->create();
    $cancelled = Booking::factory()->cancelled()->window($now->copy()->setTime(14, 0), $now->copy()->setTime(18, 0))->create(); // ignored
    Booking::factory()->approved()->window($now->copy()->addDays(20), $now->copy()->addDays(20)->addHours(8))->create(); // outside week

    // Attach payments to an existing booking so the factory does not create extra pending bookings.
    $payment = Payment::factory()->for($cancelled);
    $payment->verified()->create(['amount_cents' => 150_000, 'verified_at' => $now->copy()->subDays(2)]);
    $payment->verified()->create(['amount_cents' => 99_900, 'verified_at' => $now->copy()->subMonth()]); // last month
    $payment->create(['amount_cents' => 50_000]); // pending

    expect(app(DashboardService::class)->summary($now))->toBe([
        'pending' => 1,
        'arrivals_today' => 1,
        'upcoming_week' => 2,
        'revenue_month_cents' => 150_000,
    ]);

    Carbon::setTestNow();
});

it('lists upcoming active bookings soonest first', function () {
    $later = Booking::factory()->approved()->window(now()->addDays(5), now()->addDays(5)->addHours(4))->create();
    $sooner = Booking::factory()->window(now()->addDay(), now()->addDay()->addHours(4))->create();
    Booking::factory()->rejected()->window(now()->addDays(2), now()->addDays(2)->addHours(4))->create();

    expect(app(DashboardService::class)->upcoming()->pluck('id')->all())->toBe([$sooner->id, $later->id]);
});

it('renders the dashboard with upcoming bookings', function () {
    $booking = Booking::factory()->window(now()->addDay(), now()->addDay()->addHours(4))->create();

    $this->actingAs(User::factory()->owner()->create())->get('/admin')
        ->assertOk()
        ->assertSee($booking->reference_code)
        ->assertSee($booking->guest_name);
});
