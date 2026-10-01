<?php

/*
| M6: walk-in creation (source admin, optional payment + approval), owner-only price override (D-031),
| receipts (HTML + PDF).
*/

use App\Enums\ActivityAction;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\Booking\PriceOverrideNotAllowedException;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\Booking\BookingService;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 09:00'));
    $this->seed(SettingSeeder::class);
    $this->owner = User::factory()->owner()->create();
    $this->staff = User::factory()->create();
    $this->day = dayPackage();
});

afterEach(fn () => Carbon::setTestNow());

/**
 * Walk-in form input.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function walkIn(App\Models\Package $package, array $overrides = []): array
{
    return array_merge([
        'package_id' => (string) $package->id,
        'date' => '2027-01-01', // today: inside the guest lead time
        'guest_count' => '20',
        'guest_name' => 'Pedro Penduko',
        'guest_phone' => '0917 555 0000',
    ], $overrides);
}

it('creates a walk-in for today as staff, marked as admin-created', function () {
    $this->actingAs($this->staff)->get(route('admin.bookings.create'))->assertOk()->assertSee('Walk-in booking')->assertDontSee('Price override');

    $this->post(route('admin.bookings.store'), walkIn($this->day, ['date' => '2027-01-02']))->assertSessionHasNoErrors();

    $booking = Booking::query()->sole();
    expect($booking->source)->toBe(BookingSource::Admin)
        ->and($booking->created_by)->toBe($this->staff->id)
        ->and($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->guest_phone)->toBe('+639175550000');
    $this->get(route('admin.bookings.show', $booking))->assertSee('Walk-in / admin by '.$this->staff->name);
});

it('records a payment and approves a walk-in in one go', function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 06:00'));

    $this->actingAs($this->staff)->post(route('admin.bookings.store'), walkIn($this->day, ['payment_amount' => '3500', 'payment_type' => 'downpayment', 'payment_reference' => 'CASH', 'approve' => '1']))
        ->assertSessionHasNoErrors()->assertSessionHas('success');

    $booking = Booking::query()->sole();
    expect($booking->status)->toBe(BookingStatus::Approved)
        ->and(Payment::query()->sole()->amount_cents)->toBe(350_000);
});

it('creates nothing when approval is requested without enough payment', function () {
    $this->actingAs($this->staff)->post(route('admin.bookings.store'), walkIn($this->day, ['date' => '2027-01-02', 'payment_amount' => '100', 'approve' => '1']))
        ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'still due'));

    expect(Booking::query()->count())->toBe(0)->and(Payment::query()->count())->toBe(0);
});

it('lets the owner override the price with a logged reason', function () {
    $this->actingAs($this->owner)->get(route('admin.bookings.create'))->assertSee('Price override');

    $this->post(route('admin.bookings.store'), walkIn($this->day, ['date' => '2027-01-02', 'price_override' => '5000', 'price_override_reason' => 'Barangay discount']))
        ->assertSessionHasNoErrors();

    $booking = Booking::query()->sole();
    expect($booking->total_amount_cents)->toBe(500_000)
        ->and($booking->original_total_cents)->toBe(700_000)
        ->and($booking->downpayment_required_cents)->toBe(250_000)
        ->and($booking->price_override_reason)->toBe('Barangay discount');

    $log = ActivityLog::query()->where('action', ActivityAction::BookingPriceOverridden->value)->sole();
    expect($log->user_id)->toBe($this->owner->id)
        ->and($log->properties)->toEqualCanonicalizing(['from' => 700_000, 'to' => 500_000, 'reason' => 'Barangay discount']);
    $this->get(route('admin.bookings.show', $booking))->assertSee('Price overridden from ₱7,000.00')->assertSee('Barangay discount');
});

it('forbids price overrides by staff and requires a reason', function () {
    $this->actingAs($this->staff)->post(route('admin.bookings.store'), walkIn($this->day, ['price_override' => '1', 'price_override_reason' => 'Friend']))->assertForbidden();

    $this->actingAs($this->owner)->post(route('admin.bookings.store'), walkIn($this->day, ['price_override' => '1']))
        ->assertSessionHasErrors(['price_override_reason' => 'A reason is required when overriding the price.']);

    expect(Booking::query()->count())->toBe(0);
});

it('rejects overrides by non-owners in the service too', function () {
    app(BookingService::class)->create(bookingData($this->day, '2027-01-05', ['price_override_cents' => 100, 'price_override_reason' => 'x']), $this->staff);
})->throws(PriceOverrideNotAllowedException::class, 'Only the owner');

it('allows a walk-in whose window has started but refuses one that has ended', function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 10:00'));
    $this->actingAs($this->staff)->post(route('admin.bookings.store'), walkIn($this->day))->assertSessionHas('success');

    Carbon::setTestNow(Carbon::parse('2027-01-02 18:00'));
    $this->post(route('admin.bookings.store'), walkIn($this->day, ['date' => '2027-01-02', 'guest_phone' => '09170000001']))
        ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'already ended'));

    expect(Booking::query()->count())->toBe(1);
});

it('surfaces slot conflicts on walk-ins', function () {
    app(BookingService::class)->create(bookingData(fullDayPackage(), '2027-01-02'));

    $this->actingAs($this->staff)->post(route('admin.bookings.store'), walkIn($this->day, ['date' => '2027-01-02']))
        ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'already booked'))
        ->assertSessionHasInput('guest_name', 'Pedro Penduko');
});

it('renders a printable receipt and a PDF with resort info', function () {
    $booking = app(BookingService::class)->create(bookingData($this->day, '2027-01-04'));
    Payment::factory()->for($booking)->verified()->create(['amount_cents' => 350_000, 'reference_no' => 'GC-777']);

    $this->actingAs($this->staff)->get(route('admin.bookings.receipt', $booking))->assertOk()
        ->assertSee('Wonderpool Garden Resort')
        ->assertSee($booking->reference_code)
        ->assertSee('GC-777')
        ->assertSeeInOrder(['Total paid', '₱3,500.00', 'Balance due', '₱3,500.00'])
        ->assertSee('window.print()', false);

    $pdf = $this->get(route('admin.bookings.receipt', [$booking, 'pdf' => 1]))->assertOk();
    expect($pdf->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($pdf->headers->get('Content-Disposition'))->toContain("receipt-{$booking->reference_code}.pdf")
        ->and(substr((string) $pdf->getContent(), 0, 4))->toBe('%PDF');
});

it('keeps receipts away from guests', function () {
    $booking = app(BookingService::class)->create(bookingData($this->day, '2027-01-04'));

    $this->get(route('admin.bookings.receipt', $booking))->assertRedirect('/admin/login');
});
