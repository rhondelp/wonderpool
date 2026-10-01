<?php

/*
| M5: rate limits on quote/booking/upload/track and the Turnstile hook (off by default) (D-028).
*/

use App\Models\Booking;
use App\Rules\Turnstile;
use App\Services\Booking\GuestBookingService;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 09:00'));
    $this->seed(SettingSeeder::class);
    $this->day = dayPackage();
});

afterEach(fn () => Carbon::setTestNow());

it('rate limits public endpoints per IP', function (string $method, Closure $uri, int $limit) {
    $uri = $uri($this->day);

    for ($i = 0; $i < $limit; $i++) {
        expect($this->call($method, $uri, ['guest_count' => 'x'])->status())->not->toBe(429);
    }

    $this->call($method, $uri, ['guest_count' => 'x'])->assertStatus(429);
})->with([
    'quote 60/min' => ['POST', fn () => '/book/quote', 60],
    'calendar 60/min' => ['GET', fn ($package) => "/book/availability?package_id={$package->id}", 60],
    'book 5/min' => ['POST', fn () => '/book', 5],
    'track 10/min' => ['POST', fn () => '/track', 10],
]);

it('rate limits proof uploads', function () {
    Storage::fake('local');
    $booking = app(GuestBookingService::class)->book(bookingData($this->day, '2027-01-04'));

    for ($i = 0; $i < 10; $i++) {
        $this->post(route('book.payment.store', $booking), []);
    }

    $this->post(route('book.payment.store', $booking), ['proof' => UploadedFile::fake()->image('a.jpg')])->assertStatus(429);
});

it('limits bookings to 20 per day per IP', function () {
    for ($i = 0; $i < 20; $i++) {
        Carbon::setTestNow(Carbon::parse('2027-01-01 09:00')->addMinutes($i * 2));
        $this->post('/book', ['guest_count' => 'x']);
    }

    $this->post('/book', ['guest_count' => 'x'])->assertStatus(429);
});

it('keeps Turnstile off by default', function () {
    expect(Turnstile::enabled())->toBeFalse();
    $this->get('/book')->assertDontSee('challenges.cloudflare.com');
});

it('requires a valid Turnstile token when enabled', function () {
    config(['wonderpool.turnstile.enabled' => true, 'wonderpool.turnstile.site_key' => 'site-test', 'wonderpool.turnstile.secret_key' => 'secret-test']);
    Http::fake([Turnstile::VERIFY_URL => Http::sequence()->push(['success' => false])->push(['success' => true])]);

    $form = [
        'package_id' => $this->day->id, 'date' => '2027-01-04', 'guest_count' => 10, 'guest_name' => 'Ana Reyes',
        'guest_phone' => '09181234567', 'terms' => '1',
    ];

    $this->get('/book')->assertSee('data-sitekey="site-test"', false)->assertSee('challenges.cloudflare.com/turnstile');

    $this->post('/book', $form)->assertSessionHasErrors(['cf-turnstile-response' => 'Please complete the security check.']);
    $this->post('/book', $form + ['cf-turnstile-response' => 'bad-token'])->assertSessionHasErrors(['cf-turnstile-response' => 'The security check failed. Please try again.']);
    $this->post('/book', $form + ['cf-turnstile-response' => 'good-token'])->assertSessionHasNoErrors();

    expect(Booking::query()->count())->toBe(1);
    Http::assertSent(fn ($request): bool => $request['secret'] === 'secret-test' && $request['response'] === 'good-token');
});
