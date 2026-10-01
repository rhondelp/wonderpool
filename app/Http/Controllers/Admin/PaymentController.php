<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentType;
use App\Exceptions\Booking\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Booking\RecordPaymentRequest;
use App\Http\Requests\Admin\Booking\RejectPaymentRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\Booking\PaymentProofService;
use App\Services\Booking\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payments from the booking detail page: record, verify, reject/void, and the private proof viewer (D-032).
 */
class PaymentController extends Controller
{
    /**
     * @param  PaymentService  $payments  Payment rules
     */
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * Record a payment received outside the website (verified immediately).
     */
    public function store(RecordPaymentRequest $request, Booking $booking): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->payments->record($booking, PaymentType::from($request->string('type')->toString()), $request->amountCents(), $user, $request->input('reference_no'), $request->input('notes'));
        } catch (BookingException $e) {
            return redirect()->route('admin.bookings.show', $booking)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Payment recorded.');
    }

    /**
     * Verify a pending payment.
     */
    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('verify', $payment);
        /** @var User $user */
        $user = $request->user();

        try {
            $this->payments->verify($payment, $user);
        } catch (BookingException $e) {
            return redirect()->route('admin.bookings.show', $payment->booking_id)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookings.show', $payment->booking_id)->with('success', 'Payment verified.');
    }

    /**
     * Reject a pending payment or void a verified one (owner).
     */
    public function reject(RejectPaymentRequest $request, Payment $payment): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->payments->reject($payment, $user, $request->string('reason')->toString());
        } catch (BookingException $e) {
            return redirect()->route('admin.bookings.show', $payment->booking_id)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookings.show', $payment->booking_id)->with('success', 'Payment rejected. The guest can upload a new proof while the booking is pending.');
    }

    /**
     * Streams the proof file from the private disk inline; never cached by shared caches.
     */
    public function proof(Payment $payment): StreamedResponse
    {
        Gate::authorize('viewProof', $payment);

        $disk = Storage::disk(PaymentProofService::DISK);
        abort_unless($payment->hasProof() && $disk->exists((string) $payment->proof_path), 404);

        return $disk->response((string) $payment->proof_path, 'proof-'.$payment->id.'.'.pathinfo((string) $payment->proof_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
