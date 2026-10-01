<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentState;
use App\Enums\PaymentType;
use App\Exceptions\Booking\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Booking\AdminQuoteRequest;
use App\Http\Requests\Admin\Booking\StoreWalkInRequest;
use App\Models\AddOn;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Services\Booking\BookingAdminService;
use App\Services\Booking\BookingService;
use App\Services\Booking\GuestBookingService;
use App\Services\Booking\PaymentService;
use App\Services\PublicContentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Admin bookings (owners and staff, D-029): list, walk-in creation, detail page, receipt,
 * and the admin calendar/quote JSON shared by the walk-in and reschedule forms.
 */
class BookingController extends Controller
{
    /**
     * @param  BookingAdminService  $admin  Admin workflow
     */
    public function __construct(private readonly BookingAdminService $admin)
    {
    }

    /**
     * Index: status tabs with counts, filters, search, sorting.
     */
    public function index(Request $request, PaymentService $payments): View
    {
        Gate::authorize('viewAny', Booking::class);

        $bookings = $this->admin->listing($request->only(['q', 'status', 'package', 'payment', 'from', 'to', 'sort', 'dir']));

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'summaries' => collect($bookings->items())->mapWithKeys(fn (Booking $b): array => [$b->id => $payments->summary($b)]),
            'counts' => $this->admin->statusCounts(),
            'packages' => Package::query()->ordered()->get(),
            'statuses' => BookingStatus::cases(),
            'paymentStates' => PaymentState::cases(),
        ]);
    }

    /**
     * Walk-in / phone booking form.
     */
    public function create(Request $request, PublicContentService $content): View
    {
        Gate::authorize('create', Booking::class);
        $packages = $content->packages();

        return view('admin.bookings.create', [
            'packages' => $packages,
            'addOns' => AddOn::query()->active()->orderBy('name')->get(),
            'selectedPackage' => $packages->firstWhere('id', $request->integer('package')) ?? $packages->first(),
            'paymentTypes' => PaymentType::cases(),
        ]);
    }

    /**
     * Creates the walk-in (optionally with payment and approval).
     */
    public function store(StoreWalkInRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $booking = $this->admin->createWalkIn($request->walkInData(), $user);
        } catch (BookingException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookings.show', $booking)->with('success', "Booking {$booking->reference_code} created.");
    }

    /**
     * Detail page.
     */
    public function show(Booking $booking, BookingService $engine, PaymentService $payments, GuestBookingService $guest): View
    {
        Gate::authorize('view', $booking);
        $booking->load(['package', 'addOns', 'payments.verifier', 'payments.recorder', 'approver', 'creator']);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'summary' => $payments->summary($booking),
            'timeline' => $this->admin->timeline($booking),
            'windowLabel' => $guest->windowLabel($booking->starts_at, $booking->ends_at),
            'can' => [
                'approve' => $engine->canTransition($booking, BookingStatus::Approved),
                'reject' => $engine->canTransition($booking, BookingStatus::Rejected),
                'cancel' => $engine->canTransition($booking, BookingStatus::Cancelled),
                'complete' => $engine->canTransition($booking, BookingStatus::Completed) && $booking->ends_at->isPast(),
                'reschedule' => in_array($booking->status, BookingStatus::blocking(), true),
            ],
            'packages' => Package::query()->active()->ordered()->get(),
            'paymentTypes' => PaymentType::cases(),
        ]);
    }

    /**
     * Printable receipt; ?pdf=1 downloads a PDF.
     */
    public function receipt(Request $request, Booking $booking, PaymentService $payments, GuestBookingService $guest, PublicContentService $content): View|Response
    {
        Gate::authorize('view', $booking);
        $booking->load(['package', 'addOns', 'payments']);

        $data = [
            'booking' => $booking,
            'summary' => $payments->summary($booking),
            'windowLabel' => $guest->windowLabel($booking->starts_at, $booking->ends_at),
            'site' => $content->site(),
            'pdf' => $request->boolean('pdf'),
        ];

        if ($request->boolean('pdf')) {
            return Pdf::loadView('admin.bookings.receipt', $data)->setPaper('a5')->download("receipt-{$booking->reference_code}.pdf");
        }

        return view('admin.bookings.receipt', $data);
    }

    /**
     * Admin calendar JSON (?package_id, month, booking = id to ignore). Only past days are closed.
     */
    public function calendar(Request $request, GuestBookingService $guest): JsonResponse
    {
        Gate::authorize('create', Booking::class);
        $validated = $request->validate([
            'package_id' => ['required', 'integer'],
            'month' => ['nullable', 'date_format:Y-m'],
            'booking' => ['nullable', 'integer'],
        ]);

        $package = Package::query()->active()->findOrFail($validated['package_id']);
        $month = isset($validated['month']) ? CarbonImmutable::parse($validated['month'].'-01') : CarbonImmutable::now();

        return response()->json($guest->calendar($package, $month, isset($validated['booking']) ? (int) $validated['booking'] : null, false));
    }

    /**
     * Admin quote JSON (no lead-time rule; optional booking id ignored in the overlap check).
     */
    public function quote(AdminQuoteRequest $request, GuestBookingService $guest): JsonResponse
    {
        $package = Package::query()->findOrFail($request->integer('package_id'));
        $date = CarbonImmutable::parse($request->string('date')->toString(), config('app.timezone'));

        return response()->json($guest->quote($package, $date, $request->integer('guest_count'), $request->addOns(), $request->ignoreBookingId(), false));
    }
}
