<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Booking\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Booking\BookingReasonRequest;
use App\Http\Requests\Admin\Booking\RescheduleBookingRequest;
use App\Http\Requests\Admin\Booking\UpdateBookingNotesRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Services\Booking\BookingAdminService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\RedirectResponse;

/**
 * Booking actions from the detail page. Every action goes through BookingAdminService →
 * BookingService (transition map, activity log, BookingStatusChanged event); rule violations
 * come back as friendly flash messages.
 */
class BookingActionController extends Controller
{
    /**
     * @param  BookingAdminService  $admin  Admin workflow
     */
    public function __construct(private readonly BookingAdminService $admin)
    {
    }

    /**
     * Approve (auto-verifies pending proofs; needs the downpayment covered).
     */
    public function approve(BookingReasonRequest $request, Booking $booking): RedirectResponse
    {
        return $this->run($booking, fn (User $user) => $this->admin->approve($booking, $user), 'Booking approved.', $request);
    }

    /**
     * Reject with a reason.
     */
    public function reject(BookingReasonRequest $request, Booking $booking): RedirectResponse
    {
        return $this->run($booking, fn (User $user) => $this->admin->reject($booking, $user, (string) $request->reason()), 'Booking rejected.', $request);
    }

    /**
     * Cancel (optional reason).
     */
    public function cancel(BookingReasonRequest $request, Booking $booking): RedirectResponse
    {
        return $this->run($booking, fn (User $user) => $this->admin->cancel($booking, $user, $request->reason()), 'Booking cancelled. The date is free again.', $request);
    }

    /**
     * Mark completed (after the stay).
     */
    public function complete(BookingReasonRequest $request, Booking $booking): RedirectResponse
    {
        return $this->run($booking, fn (User $user) => $this->admin->complete($booking, $user), 'Booking marked as completed.', $request);
    }

    /**
     * Move to another date/package.
     */
    public function reschedule(RescheduleBookingRequest $request, Booking $booking): RedirectResponse
    {
        $package = Package::query()->findOrFail($request->integer('package_id'));
        $date = CarbonImmutable::parse($request->string('date')->toString(), config('app.timezone'));

        return $this->run($booking, fn (User $user) => $this->admin->reschedule($booking, $date, $user, $package, $request->boolean('reprice')), 'Booking rescheduled.', $request);
    }

    /**
     * Save internal notes.
     */
    public function notes(UpdateBookingNotesRequest $request, Booking $booking): RedirectResponse
    {
        return $this->run($booking, fn (User $user) => $this->admin->updateNotes($booking, $user, $request->input('admin_notes')), 'Notes saved.', $request);
    }

    /**
     * Runs an action and redirects to the detail page with a success or error flash.
     *
     * @param  Closure(User): mixed  $action
     */
    private function run(Booking $booking, Closure $action, string $success, \Illuminate\Http\Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $action($user);
        } catch (BookingException $e) {
            return redirect()->route('admin.bookings.show', $booking)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookings.show', $booking)->with('success', $success);
    }
}
