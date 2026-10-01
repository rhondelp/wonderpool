<?php

/*
| M5: guest booking flow — create, payment page, private proof upload, success page,
| friendly errors (slot taken, lead time, validation), session-guarded booking pages.
| "Now" is fixed at 2027-01-01 09:00 (Asia/Manila); 2027-01-04 is a Monday.
*/

use App\Enums\ActivityAction;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\AddOn;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Booking\BookingService;
use App\Services\Booking\GuestBookingService;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 09:00'));
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(SettingSeeder::class);
    $this->day = dayPackage();
});

afterEach(fn () => Carbon::setTestNow());

/**
 * Valid booking form input.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bookingForm(App\Models\Package $package, array $overrides = []): array
{
    return array_merge([
        'package_id' => (string) $package->id,
        'date' => '2027-01-04',
        'guest_count' => '25',
        'guest_name' => 'Maria Santos',
        'guest_phone' => '0917 123 4567',
        'guest_email' => 'Maria@Example.com',
        'event_type' => 'Birthday',
        'notes' => 'Bringing a cake.',
        'terms' => '1',
        'website' => '',
    ], $overrides);
}

it('books, shows the payment step, stores the proof privately and shows the reference', function () {
    $addOn = AddOn::factory()->create(['price_cents' => 50_000]);

    $response = $this->post('/book', bookingForm($this->day, ['add_ons' => [$addOn->id => '2']]));

    $booking = Booking::query()->sole();
    $response->assertRedirect(route('book.payment', $booking));
    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->guest_phone)->toBe('+639171234567')
        ->and($booking->guest_email)->toBe('maria@example.com')
        ->and($booking->total_amount_cents)->toBe(800_000)
        ->and($booking->starts_at->format('Y-m-d H:i'))->toBe('2027-01-04 07:00');

    $this->get(route('book.payment', $booking))->assertOk()
        ->assertSee($booking->reference_code)
        ->assertSee('How to pay ₱4,000.00')
        ->assertSee('GCash');

    $this->post(route('book.payment.store', $booking), [
        'proof' => UploadedFile::fake()->image('receipt.png', 1200, 2400),
        'reference_no' => 'GC-12345',
    ])->assertRedirect(route('book.done', $booking));

    $payment = Payment::query()->sole();
    expect($payment->booking_id)->toBe($booking->id)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount_cents)->toBe(400_000)
        ->and($payment->reference_no)->toBe('GC-12345')
        ->and($payment->proof_path)->toStartWith("payment-proofs/{$booking->id}/")->toEndWith('.jpg');
    Storage::disk('local')->assertExists($payment->proof_path);
    expect(Storage::disk('public')->allFiles())->toBe([]);

    [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($payment->proof_path));
    expect([$width, $height])->toBe([1000, 2000]); // longest edge capped at 2000 px

    expect(ActivityLog::query()->where('action', ActivityAction::PaymentProofUploaded->value)->where('subject_id', $booking->id)->exists())->toBeTrue();

    $this->get(route('book.done', $booking))->assertOk()
        ->assertSee($booking->reference_code)
        ->assertSee('Payment proof received')
        ->assertSee('Copy code');
});

it('strips image metadata (EXIF/GPS) from payment proofs', function () {
    $booking = app(GuestBookingService::class)->book(bookingData($this->day, '2027-01-04', ['guest_phone' => '09171234567']));

    // Inject an APP1/EXIF segment with a recognizable marker right after the JPEG SOI marker.
    $fake = UploadedFile::fake()->image('receipt.jpg', 400, 300);
    $jpeg = (string) file_get_contents($fake->getRealPath());
    $exif = "Exif\0\0GPS-SECRET-LOCATION-MARKER";
    $withExif = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
    expect($withExif)->toContain('GPS-SECRET-LOCATION-MARKER');

    $this->post(route('book.payment.store', $booking), ['proof' => UploadedFile::fake()->createWithContent('receipt.jpg', $withExif)])
        ->assertSessionHasNoErrors();

    $stored = Storage::disk('local')->get(Payment::query()->sole()->proof_path);
    expect($stored)->not->toContain('GPS-SECRET-LOCATION-MARKER')
        ->and(getimagesizefromstring($stored)['mime'])->toBe('image/jpeg');
});

it('accepts a PDF receipt', function () {
    $booking = app(GuestBookingService::class)->book(bookingData($this->day, '2027-01-04', ['guest_phone' => '09171234567']));

    $this->post(route('book.payment.store', $booking), ['proof' => UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF")])
        ->assertRedirect(route('book.done', $booking));

    expect(Payment::query()->sole()->proof_path)->toEndWith('.pdf');
});

it('rejects invalid proof uploads', function (Closure $file, string $message) {
    $booking = app(GuestBookingService::class)->book(bookingData($this->day, '2027-01-04', ['guest_phone' => '09171234567']));

    $this->from(route('book.payment', $booking))->post(route('book.payment.store', $booking), ['proof' => $file()])
        ->assertSessionHasErrors(['proof' => $message]);

    expect(Payment::query()->count())->toBe(0);
})->with([
    'gif' => [fn () => UploadedFile::fake()->image('a.gif'), 'Upload a JPG, PNG, WebP image or a PDF.'],
    'text file' => [fn () => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'), 'Upload a JPG, PNG, WebP image or a PDF.'],
    'unreadable image' => [fn () => UploadedFile::fake()->createWithContent('fake.jpg', 'just text'), 'We could not read that image. Please upload a clear screenshot (JPG, PNG or WebP) or a PDF.'],
    'over 5 MB' => [fn () => UploadedFile::fake()->image('big.jpg')->size(5121), 'The file is too large. The limit is 5 MB.'],
    'missing' => [fn () => null, 'Please choose a screenshot or PDF of your payment receipt.'],
]);

it('surfaces a taken slot as a friendly error with the form refilled', function () {
    app(BookingService::class)->create(bookingData(fullDayPackage(), '2027-01-04'));

    $this->from('/book')->post('/book', bookingForm($this->day, ['website' => '']))
        ->assertRedirect(route('book', ['package' => $this->day->id]))
        ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'already booked'))
        ->assertSessionHasInput('guest_name', 'Maria Santos');

    expect(Booking::query()->count())->toBe(1);

    $this->get(route('book', ['package' => $this->day->id]))->assertSee('already booked')->assertSee('value="Maria Santos"', false);
});

it('enforces the lead time and maximum advance for guests', function (string $date) {
    $this->post('/book', bookingForm($this->day, ['date' => $date]))
        ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'at least 24 hours'));

    expect(Booking::query()->count())->toBe(0);
})->with([
    'tomorrow 07:00 is 22 hours away' => '2027-01-02',
    'beyond 365 days' => '2028-01-05',
]);

it('validates the booking form with friendly messages', function (array $overrides, string $field, ?string $message = null) {
    $response = $this->post('/book', bookingForm($this->day, $overrides));

    $message ? $response->assertSessionHasErrors([$field => $message]) : $response->assertSessionHasErrors($field);
    expect(Booking::query()->count())->toBe(0);
})->with([
    'bad phone' => [['guest_phone' => '1234'], 'guest_phone', 'Enter a Philippine mobile number, e.g. 0917 123 4567.'],
    'missing name' => [['guest_name' => ''], 'guest_name', 'Please enter the name of the person booking.'],
    'bad email' => [['guest_email' => 'nope'], 'guest_email', null],
    'terms not accepted' => [['terms' => '0'], 'terms', 'Please confirm that you have read the house rules and payment instructions.'],
    'honeypot filled' => [['website' => 'http://spam.example'], 'website', null],
    'bad date' => [['date' => '04/01/2027'], 'date', null],
    'inactive package' => [['package_id' => '999999'], 'package_id', 'Please choose one of the available packages.'],
]);

it('reports guest-count and add-on problems from the pricing rules', function () {
    $this->post('/book', bookingForm($this->day, ['guest_count' => '80']))
        ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'allows 1 to 50 guests'));
});

it('keeps booking pages private to the browser that made the booking', function () {
    $booking = Booking::factory()->for($this->day)->create();

    $this->get(route('book.payment', $booking))->assertNotFound();
    $this->get(route('book.done', $booking))->assertNotFound();
    $this->post(route('book.payment.store', $booking), ['proof' => UploadedFile::fake()->image('a.jpg')])->assertNotFound();
});

it('refuses proofs for bookings that are no longer pending', function () {
    $booking = app(GuestBookingService::class)->book(bookingData($this->day, '2027-01-04', ['guest_phone' => '09171234567']));
    app(BookingService::class)->transition($booking, BookingStatus::Cancelled, null);

    $this->from(route('book.payment', $booking))->post(route('book.payment.store', $booking), ['proof' => UploadedFile::fake()->image('a.jpg')])
        ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'only be sent while the booking is pending'));

    expect(Payment::query()->count())->toBe(0);
});

it('renders the booking page with the package preselected and steps visible without JS', function () {
    $night = nightPackage();

    $this->get(route('book', ['package' => $night->id]))->assertOk()
        ->assertSee('value="'.$night->id.'" x-model="packageId" class="sr-only" checked', false)
        ->assertSee('1. Choose your package and date')
        ->assertSee('2. Your details')
        ->assertSee('3. Review and confirm')
        ->assertSee('name="website"', false)
        ->assertDontSee('cf-turnstile');
});
