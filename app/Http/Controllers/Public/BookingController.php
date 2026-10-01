<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\Booking\BookingException;
use App\Exceptions\Booking\InvalidPaymentProofException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\QuoteRequest;
use App\Http\Requests\Public\StoreBookingRequest;
use App\Http\Requests\Public\UploadPaymentProofRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Rules\Turnstile;
use App\Services\Booking\GuestBookingService;
use App\Services\Booking\PaymentProofService;
use App\Services\PublicContentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Guest booking flow: 3-step form, calendar/quote JSON, creation, payment proof, success page.
 * All availability/pricing comes from the M4 services through GuestBookingService.
 */
class BookingController extends Controller
{
    /**
     * @param  GuestBookingService  $guest  Guest-facing booking facade
     */
    public function __construct(private readonly GuestBookingService $guest)
    {
    }

    /**
     * The 3-step booking page (?package= preselects a package).
     */
    public function create(Request $request, PublicContentService $content): View
    {
        $packages = $content->packages();

        return view('public.book.index', [
            'packages' => $packages,
            'addOns' => $content->addOns(),
            'selectedPackage' => $packages->firstWhere('id', $request->integer('package')) ?? $packages->first(),
            'paymentInstructions' => $this->guest->paymentInstructions(),
            'cancellationPolicy' => $content->text('content.cancellation_policy'),
            'turnstile' => Turnstile::enabled(),
        ]);
    }

    /**
     * Calendar month JSON for one package (?package_id=&month=YYYY-MM).
     */
    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => ['required', 'integer'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $package = Package::query()->active()->findOrFail($validated['package_id']);
        $month = isset($validated['month']) ? CarbonImmutable::parse($validated['month'].'-01') : CarbonImmutable::now();

        return response()->json($this->guest->calendar($package, $month));
    }

    /**
     * Live quote JSON: availability + price breakdown.
     */
    public function quote(QuoteRequest $request): JsonResponse
    {
        $package = Package::query()->findOrFail($request->integer('package_id'));
        $date = CarbonImmutable::parse($request->string('date')->toString(), config('app.timezone'));

        return response()->json($this->guest->quote($package, $date, $request->integer('guest_count'), $request->addOns()));
    }

    /**
     * Creates the booking, then sends the guest to the payment step.
     */
    public function store(StoreBookingRequest $request): RedirectResponse
    {
        try {
            $booking = $this->guest->book($request->bookingData());
        } catch (BookingException $e) {
            return redirect()->route('book', ['package' => $request->integer('package_id')])
                ->withInput($request->except(StoreBookingRequest::HONEYPOT))
                ->with('error', $e->getMessage());
        }

        return redirect()->route('book.payment', $booking)->with('success', 'Your booking request has been saved. One more step: send your payment proof.');
    }

    /**
     * Payment step: summary, instructions and proof upload (only for this browser's bookings).
     */
    public function payment(Booking $booking): View
    {
        abort_unless($this->guest->canAccess($booking), 404);

        return view('public.book.payment', [
            'booking' => $booking->load(['package', 'addOns', 'payments']),
            'windowLabel' => $this->guest->windowLabel($booking->starts_at, $booking->ends_at),
            'paymentInstructions' => $this->guest->paymentInstructions(),
        ]);
    }

    /**
     * Stores the payment proof (private disk), then shows the success page or the tracking page.
     */
    public function uploadProof(UploadPaymentProofRequest $request, Booking $booking, PaymentProofService $proofs): RedirectResponse
    {
        abort_unless($this->guest->canAccess($booking), 404);

        try {
            $proofs->store($booking, $request->proof(), $request->filled('reference_no') ? $request->string('reference_no')->trim()->toString() : null);
        } catch (InvalidPaymentProofException $e) {
            return back()->withErrors(['proof' => $e->getMessage()]);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $request->input('return') === 'track'
            ? redirect()->route('track.show', $booking)->with('success', 'Thank you! We received your payment proof.')
            : redirect()->route('book.done', $booking);
    }

    /**
     * Success page with the reference code and next steps.
     */
    public function done(Booking $booking, PublicContentService $content): View
    {
        abort_unless($this->guest->canAccess($booking), 404);

        return view('public.book.done', [
            'booking' => $booking->load(['package', 'payments']),
            'windowLabel' => $this->guest->windowLabel($booking->starts_at, $booking->ends_at),
            'nextSteps' => $content->text('content.booking_success_note'),
        ]);
    }
}
