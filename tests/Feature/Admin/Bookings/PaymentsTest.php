<?php

/*
| M6: record / verify / reject / void payments, payment summary, private proof viewer (D-030, D-032).
*/

use App\Enums\ActivityAction;
use App\Enums\PaymentState;
use App\Enums\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Services\Booking\PaymentProofService;
use App\Services\Booking\PaymentService;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01 09:00'));
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(SettingSeeder::class);
    $this->owner = User::factory()->owner()->create();
    $this->staff = User::factory()->create();
    $this->booking = app(BookingService::class)->create(bookingData(dayPackage(), '2027-01-04'));
});

afterEach(fn () => Carbon::setTestNow());

it('records a manual payment as verified and logs it', function () {
    $this->actingAs($this->staff)->post(route('admin.bookings.payments.store', $this->booking), ['type' => 'downpayment', 'amount' => '3500.50', 'reference_no' => 'GC-1', 'notes' => 'Paid at gate'])
        ->assertSessionHas('success', 'Payment recorded.');

    $payment = Payment::query()->sole();
    expect($payment->status)->toBe(PaymentStatus::Verified)
        ->and($payment->amount_cents)->toBe(350_050)
        ->and($payment->recorded_by)->toBe($this->staff->id)
        ->and($payment->verified_by)->toBe($this->staff->id)
        ->and($payment->notes)->toBe('Paid at gate')
        ->and(ActivityLog::query()->where('action', ActivityAction::PaymentRecorded->value)->where('subject_id', $this->booking->id)->exists())->toBeTrue();
});

it('validates recorded payments', function (array $input, string $field) {
    $this->actingAs($this->staff)->post(route('admin.bookings.payments.store', $this->booking), $input + ['type' => 'balance', 'amount' => '100'])->assertSessionHasErrors($field);
})->with([
    'zero amount' => [['amount' => '0'], 'amount'],
    'three decimals' => [['amount' => '1.234'], 'amount'],
    'unknown type' => [['type' => 'tip'], 'type'],
]);

it('verifies and rejects pending proofs; only the owner can void a verified payment', function () {
    $pending = Payment::factory()->for($this->booking)->create(['amount_cents' => 350_000]);
    $other = Payment::factory()->for($this->booking)->create(['amount_cents' => 350_000]);

    $this->actingAs($this->staff)->patch(route('admin.payments.verify', $pending))->assertSessionHas('success');
    expect($pending->fresh()->status)->toBe(PaymentStatus::Verified);

    $this->patch(route('admin.payments.reject', $other), ['reason' => ''])->assertSessionHasErrors('reason');
    $this->patch(route('admin.payments.reject', $other), ['reason' => 'Amount does not match'])->assertSessionHas('success');
    expect($other->fresh()->status)->toBe(PaymentStatus::Rejected)->and($other->fresh()->rejection_reason)->toBe('Amount does not match');

    $this->patch(route('admin.payments.reject', $pending), ['reason' => 'Bounced'])->assertForbidden();   // staff cannot void
    $this->actingAs($this->owner)->patch(route('admin.payments.reject', $pending), ['reason' => 'Bounced'])->assertSessionHas('success');
    expect($pending->fresh()->status)->toBe(PaymentStatus::Rejected);

    $this->patch(route('admin.payments.verify', $pending))->assertSessionHas('error', 'This payment is already Rejected.');
});

it('tells the guest a proof was not accepted', function () {
    $payment = Payment::factory()->for($this->booking)->create(['amount_cents' => 350_000]);
    $this->actingAs($this->staff)->patch(route('admin.payments.reject', $payment), ['reason' => 'Blurry screenshot']);

    auth()->logout();
    session([\App\Services\Booking\GuestBookingService::SESSION_KEY => [$this->booking->id]]);
    $this->get(route('track.show', $this->booking))->assertSee('Payment proof not accepted')->assertSee('Blurry screenshot');
});

it('summarizes payment state, balance and downpayment coverage', function () {
    $service = app(PaymentService::class);
    expect($service->summary($this->booking))->toMatchArray(['total' => 700_000, 'verified' => 0, 'balance_due' => 700_000, 'downpayment_covered' => false, 'state' => PaymentState::Unpaid]);

    Payment::factory()->for($this->booking)->create(['amount_cents' => 350_000, 'proof_path' => 'payment-proofs/a.jpg']);
    expect($service->summary($this->booking->refresh())['state'])->toBe(PaymentState::ProofPending);

    $service->verifyPendingProofs($this->booking, $this->staff);
    expect($service->summary($this->booking->refresh()))->toMatchArray(['verified' => 350_000, 'balance_due' => 350_000, 'downpayment_covered' => true, 'state' => PaymentState::Partial]);

    $service->record($this->booking, App\Enums\PaymentType::Balance, 400_000, $this->staff);
    expect($service->summary($this->booking->refresh()))->toMatchArray(['verified' => 750_000, 'balance_due' => 0, 'state' => PaymentState::Paid]);
});

it('serves payment proofs only to signed-in admins, from the private disk', function () {
    session([\App\Services\Booking\GuestBookingService::SESSION_KEY => [$this->booking->id]]);
    $payment = app(PaymentProofService::class)->store($this->booking, UploadedFile::fake()->image('receipt.jpg', 300, 300));

    // Guest (even the one who uploaded it) → login redirect; no public URL exists.
    $this->get(route('admin.payments.proof', $payment))->assertRedirect('/admin/login');
    expect(Storage::disk('public')->allFiles())->toBe([]);

    $response = $this->actingAs($this->staff)->get(route('admin.payments.proof', $payment))->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('image/jpeg')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Content-Disposition'))->toStartWith('inline');

    $this->actingAs(User::factory()->inactive()->create())->get(route('admin.payments.proof', $payment))->assertRedirect('/admin/login');
});

it('returns 404 for payments without a proof file', function () {
    $recorded = Payment::factory()->for($this->booking)->verified()->create(['proof_path' => null]);
    $missing = Payment::factory()->for($this->booking)->create(['proof_path' => 'payment-proofs/gone.jpg']);

    $this->actingAs($this->staff)->get(route('admin.payments.proof', $recorded))->assertNotFound();
    $this->get(route('admin.payments.proof', $missing))->assertNotFound();
});
