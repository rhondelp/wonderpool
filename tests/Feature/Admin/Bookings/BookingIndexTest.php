<?php

/*
| M6: admin bookings index — tabs with counts, filters, ILIKE search, phone formats, sorting, payment state.
*/

use App\Enums\BookingStatus;
use App\Enums\PaymentState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->actingAs(User::factory()->create()); // staff may use bookings (D-029)
    $this->day = dayPackage();
    $this->night = nightPackage();

    $this->maria = Booking::factory()->for($this->day)->window(Carbon::parse('2027-02-01 07:00'), Carbon::parse('2027-02-01 17:00'))
        ->create(['guest_name' => 'Maria Santos', 'guest_phone' => '+639171111111', 'reference_code' => 'WP-2702-AAAA', 'total_amount_cents' => 700_000, 'downpayment_required_cents' => 350_000]);
    $this->jose = Booking::factory()->for($this->night)->approved()->window(Carbon::parse('2027-03-05 19:00'), Carbon::parse('2027-03-06 05:00'))
        ->create(['guest_name' => 'Jose Rizal', 'guest_phone' => '+639182222222', 'reference_code' => 'WP-2703-BBBB', 'total_amount_cents' => 900_000, 'downpayment_required_cents' => 450_000]);
    $this->ana = Booking::factory()->for($this->day)->cancelled()->window(Carbon::parse('2027-04-10 07:00'), Carbon::parse('2027-04-10 17:00'))
        ->create(['guest_name' => 'Ana Reyes', 'guest_phone' => '+639193333333', 'reference_code' => 'WP-2704-CCCC', 'total_amount_cents' => 700_000, 'downpayment_required_cents' => 350_000]);
});

it('lists bookings with status tab counts', function () {
    $this->get('/admin/bookings')->assertOk()
        ->assertSee('WP-2702-AAAA')->assertSee('WP-2703-BBBB')->assertSee('WP-2704-CCCC')
        ->assertSeeInOrder(['All', '3', 'Pending', '1', 'Approved', '1', 'Rejected', '0', 'Cancelled', '1']);
});

it('filters by status tab, package and stay dates', function () {
    $this->get('/admin/bookings?status=approved')->assertSee('WP-2703-BBBB')->assertDontSee('WP-2702-AAAA');
    $this->get("/admin/bookings?package={$this->day->id}")->assertSee('WP-2702-AAAA')->assertSee('WP-2704-CCCC')->assertDontSee('WP-2703-BBBB');
    $this->get('/admin/bookings?from=2027-03-01&to=2027-03-31')->assertSee('WP-2703-BBBB')->assertDontSee('WP-2702-AAAA')->assertDontSee('WP-2704-CCCC');
    $this->get('/admin/bookings?to=2027-02-01')->assertSee('WP-2702-AAAA')->assertDontSee('WP-2703-BBBB');
});

it('searches reference, name and phone case-insensitively and in any phone format', function (string $q, string $found) {
    $response = $this->get('/admin/bookings?q='.urlencode($q))->assertOk()->assertSee($found);
    foreach (['WP-2702-AAAA', 'WP-2703-BBBB', 'WP-2704-CCCC'] as $other) {
        if ($other !== $found) {
            $response->assertDontSee($other);
        }
    }
})->with([
    'lowercase reference' => ['wp-2703', 'WP-2703-BBBB'],
    'partial name, other case' => ['SANTOS', 'WP-2702-AAAA'],
    'local phone' => ['0919 333 3333', 'WP-2704-CCCC'],
    'partial digits' => ['1822222', 'WP-2703-BBBB'],
]);

it('sorts by whitelisted columns only', function () {
    $this->get('/admin/bookings?sort=total_amount_cents&dir=desc')->assertSeeInOrder(['WP-2703-BBBB', 'WP-2702-AAAA']);
    $this->get('/admin/bookings?sort=guest_name&dir=asc')->assertSeeInOrder(['Ana Reyes', 'Jose Rizal', 'Maria Santos']);
    $this->get('/admin/bookings?sort=password;drop&dir=asc')->assertOk()->assertSeeInOrder(['WP-2702-AAAA', 'WP-2703-BBBB', 'WP-2704-CCCC']);
});

it('filters by payment state with the same rules as the payment summary', function () {
    Payment::factory()->for($this->maria)->create(['amount_cents' => 350_000, 'proof_path' => 'payment-proofs/x.jpg']); // proof to review
    Payment::factory()->for($this->jose)->verified()->create(['amount_cents' => 900_000]);                             // paid
    Payment::factory()->for($this->ana)->verified()->create(['amount_cents' => 100_000]);                              // partial

    $expect = fn (PaymentState $state, string $reference) => $this->get('/admin/bookings?payment='.$state->value)->assertSee($reference);

    $expect(PaymentState::ProofPending, 'WP-2702-AAAA')->assertDontSee('WP-2703-BBBB')->assertSee('Proof to review');
    $expect(PaymentState::Paid, 'WP-2703-BBBB')->assertDontSee('WP-2702-AAAA');
    $expect(PaymentState::Partial, 'WP-2704-CCCC')->assertDontSee('WP-2703-BBBB');
    $this->get('/admin/bookings?payment=unpaid')->assertSee('No bookings found');
});

it('shows an empty state and the walk-in button', function () {
    $this->get('/admin/bookings?q=nobody-here')->assertSee('No bookings found')->assertSee(route('admin.bookings.create'));
});

it('shows the Bookings link to staff and owners', function () {
    $this->get('/admin')->assertSee(route('admin.bookings.index'));
    $this->actingAs(User::factory()->owner()->create())->get('/admin')->assertSee(route('admin.bookings.index'));
});

it('is not available to guests', function () {
    auth()->logout();
    $this->get('/admin/bookings')->assertRedirect('/admin/login');
    expect(BookingStatus::Pending)->toBe($this->maria->status);
});
