<?php

namespace App\Http\Controllers\Public;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\TrackBookingRequest;
use App\Models\Booking;
use App\Services\Booking\GuestBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Track a booking with reference code + mobile number, then see its status timeline.
 */
class TrackBookingController extends Controller
{
    /**
     * @param  GuestBookingService  $guest  Lookup, timeline, session access
     */
    public function __construct(private readonly GuestBookingService $guest)
    {
    }

    /**
     * Lookup form.
     */
    public function create(): View
    {
        return view('public.track.index');
    }

    /**
     * Finds the booking; the same generic error for a wrong code or a wrong phone.
     */
    public function lookup(TrackBookingRequest $request): RedirectResponse
    {
        $booking = $this->guest->find($request->string('reference')->toString(), $request->string('phone')->toString());

        if ($booking === null) {
            return back()->withInput()->withErrors(['reference' => 'We could not find a booking with that reference and mobile number. Please check both and try again.']);
        }

        $this->guest->remember($booking);

        return redirect()->route('track.show', $booking);
    }

    /**
     * Status page with timeline (and proof upload while pending).
     */
    public function show(Booking $booking): View|RedirectResponse
    {
        if (! $this->guest->canAccess($booking)) {
            return redirect()->route('track')->with('info', 'Enter your reference and mobile number to view this booking.');
        }

        return view('public.track.show', [
            'booking' => $booking->load(['package', 'payments']),
            'windowLabel' => $this->guest->windowLabel($booking->starts_at, $booking->ends_at),
            'timeline' => $this->guest->timeline($booking),
            'canUpload' => $booking->status === BookingStatus::Pending,
            'paymentInstructions' => $this->guest->paymentInstructions(),
        ]);
    }
}
