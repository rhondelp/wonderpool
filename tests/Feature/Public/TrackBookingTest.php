<?php

/*
| M5: track booking by reference + phone (both normalized), status timeline, proof upload/replace.
*/

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\Booking\BookingService;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 09:00'));
    Storage::fake('local');
    $this->seed(SettingSeeder::class);
    $this->booking = app(BookingService::class)->create(bookingData(dayPackage(), '2027-01-04', ['guest_phone' => '+639171234567']));
});

afterEach(fn () => Carbon::setTestNow());

it('requires both the reference code and the phone', function (array $input, string $field) {
    $this->post('/track', $input)->assertSessionHasErrors($field);
})->with([
    'no phone' => [['reference' => 'WP-2701-AAAA'], 'phone'],
    'no reference' => [['phone' => '09171234567'], 'reference'],
]);

it('finds a booking with a lowercase code and any phone format', function (string $reference, string $phone) {
    $code = $this->booking->reference_code;

    $this->post('/track', ['reference' => sprintf($reference, strtolower($code)), 'phone' => $phone])
        ->assertRedirect(route('track.show', $this->booking));

    $this->get(route('track.show', $this->booking))->assertOk()
        ->assertSee($code)
        ->assertSee('Booking request received')
        ->assertSee('Upload payment proof');
})->with([
    'lowercase + local phone' => ['%s', '09171234567'],
    'spaces + dashed phone' => [' %s ', '0917-123-4567'],
    'international phone' => ['%s', '+63 917 123 4567'],
]);

it('gives the same generic error for a wrong phone or a wrong code', function (Closure $input) {
    $this->from('/track')->post('/track', $input($this->booking->reference_code))
        ->assertRedirect('/track')
        ->assertSessionHasErrors(['reference' => 'We could not find a booking with that reference and mobile number. Please check both and try again.']);

    $this->get(route('track.show', $this->booking))->assertRedirect('/track');
})->with([
    'wrong phone' => [fn (string $code) => ['reference' => $code, 'phone' => '09179999999']],
    'wrong code' => [fn (string $code) => ['reference' => 'WP-2701-ZZZZ', 'phone' => '09171234567']],
    'invalid phone' => [fn (string $code) => ['reference' => $code, 'phone' => '12345']],
]);

it('requires a lookup before showing a booking', function () {
    $this->get(route('track.show', $this->booking))->assertRedirect('/track')->assertSessionHas('info');
});

it('shows status changes, reasons and hides the upload once decided', function () {
    $owner = User::factory()->owner()->create();
    app(BookingService::class)->transition($this->booking, BookingStatus::Rejected, $owner, 'Payment amount did not match.');
    $this->post('/track', ['reference' => $this->booking->reference_code, 'phone' => '09171234567']);

    $this->get(route('track.show', $this->booking))->assertOk()
        ->assertSeeInOrder(['Booking request received', 'Booking rejected', 'Payment amount did not match.'])
        ->assertDontSee('Upload payment proof')
        ->assertDontSee($owner->name);
});

it('uploads and replaces a proof from the tracking page, keeping one payment', function () {
    $this->post('/track', ['reference' => $this->booking->reference_code, 'phone' => '09171234567']);

    $this->post(route('book.payment.store', $this->booking), ['proof' => UploadedFile::fake()->image('first.jpg'), 'return' => 'track'])
        ->assertRedirect(route('track.show', $this->booking))->assertSessionHas('success');
    $first = Payment::query()->sole()->proof_path;

    $this->get(route('track.show', $this->booking))->assertSee('Replace payment proof');

    $this->post(route('book.payment.store', $this->booking), ['proof' => UploadedFile::fake()->image('second.png'), 'return' => 'track']);

    $payment = Payment::query()->sole();
    expect($payment->proof_path)->not->toBe($first);
    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($payment->proof_path);

    $this->get(route('track.show', $this->booking))->assertSeeInOrder(['Payment proof received', 'Payment proof received', 'Replaced the previous proof.']);
});

it('stores reference codes in uppercase so lookups never depend on case', function () {
    expect($this->booking->reference_code)->toBe(strtoupper($this->booking->reference_code))
        ->and(Booking::query()->where('reference_code', strtolower($this->booking->reference_code))->exists())->toBeFalse();
});
