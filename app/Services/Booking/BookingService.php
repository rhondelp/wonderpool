<?php

namespace App\Services\Booking;

use App\Enums\ActivityAction;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Exceptions\Booking\GuestCountExceededException;
use App\Exceptions\Booking\InvalidAddOnException;
use App\Exceptions\Booking\InvalidStatusTransitionException;
use App\Exceptions\Booking\PackageUnavailableException;
use App\Exceptions\Booking\PriceOverrideNotAllowedException;
use App\Exceptions\Booking\ReferenceCodeExhaustedException;
use App\Exceptions\Booking\SlotUnavailableException;
use App\Models\Booking;
use App\Models\BookingAddOn;
use App\Models\Package;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Booking lifecycle: creation, status transitions, rescheduling and pending-hold expiry.
 *
 * Double-booking protection (D-021): every write that claims a window runs inside a
 * transaction that first takes the PostgreSQL transaction-level advisory lock
 * BOOKING_LOCK_KEY, so availability check + insert are serialized across requests
 * (row locks cannot help when there is no overlapping row yet). The bookings_no_overlap
 * exclusion constraint is the second line of defense; its violation (SQLSTATE 23P01)
 * is translated into SlotUnavailableException.
 */
class BookingService
{
    /** Fixed key for pg_advisory_xact_lock; serializes all booking-window writes. */
    public const BOOKING_LOCK_KEY = 7_461_001;

    /** SQLSTATE raised by the bookings_no_overlap exclusion constraint. */
    private const EXCLUSION_VIOLATION = '23P01';

    /**
     * Allowed status changes (PLAN.md §5.5). Proof upload does not change the status.
     *
     * @var array<string, list<BookingStatus>>
     */
    public const TRANSITIONS = [
        'pending' => [BookingStatus::Approved, BookingStatus::Rejected, BookingStatus::Cancelled],
        'approved' => [BookingStatus::Completed, BookingStatus::Cancelled],
        'rejected' => [],
        'cancelled' => [],
        'completed' => [],
    ];

    /**
     * @param  AvailabilityService  $availability  Window resolution + overlap checks
     * @param  PricingService  $pricing  Quotes and snapshots
     * @param  ReferenceCodeGenerator  $references  Booking reference codes
     * @param  ActivityLogger  $logger  Audit trail
     * @param  SettingService  $settings  Pending hold hours
     */
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly ReferenceCodeGenerator $references,
        private readonly ActivityLogger $logger,
        private readonly SettingService $settings,
    ) {
    }

    /**
     * Creates a pending booking with a price snapshot (booking totals + add-on unit prices).
     * Booking window rules (lead time, max advance) are checked by the caller (M5 request);
     * admins may create walk-ins inside the lead time. An owner may override the quoted total
     * (price_override_cents + mandatory price_override_reason, D-031); the quote is kept in
     * original_total_cents and the downpayment is recomputed from the override.
     *
     * @param  array{package_id: int|string, date: string|CarbonInterface, guest_name: string, guest_phone: string, guest_email?: string|null, event_type?: string|null, guest_count: int|string, notes?: string|null, add_ons?: array<int|string, int|string>, admin_notes?: string|null, source?: BookingSource, created_by?: int|null, price_override_cents?: int|null, price_override_reason?: string|null}  $data  Validated input; date = start date (Y-m-d)
     * @param  User|null  $actor  Admin creating it (null = guest)
     *
     * @throws PackageUnavailableException When the package is missing or inactive
     * @throws SlotUnavailableException When the window is taken or blocked
     * @throws GuestCountExceededException When the guest count does not fit the package
     * @throws InvalidAddOnException When an add-on is unknown/inactive or its quantity is invalid
     * @throws ReferenceCodeExhaustedException When no free reference code is found
     * @throws PriceOverrideNotAllowedException When a non-owner overrides the price, the reason is missing or the price is negative
     */
    public function create(array $data, ?User $actor = null): Booking
    {
        return $this->guardOverlap(fn (): Booking => DB::transaction(function () use ($data, $actor): Booking {
            $this->lock();

            $package = Package::query()->find($data['package_id']);
            if ($package === null || ! $package->is_active) {
                throw new PackageUnavailableException('This package is not available for booking.');
            }

            $date = $this->day($data['date']);
            $window = $this->availability->resolveWindow($package, $date);
            $this->ensureAvailable($window['starts_at'], $window['ends_at']);

            $quote = $this->pricing->quote($package, $date, $data['add_ons'] ?? [], (int) $data['guest_count']);
            [$total, $downpayment, $override] = $this->applyOverride($quote, $data, $actor);

            $booking = Booking::query()->create([
                'reference_code' => $this->references->generate($window['starts_at']),
                'package_id' => $package->id,
                'guest_name' => trim($data['guest_name']),
                'guest_phone' => $data['guest_phone'],
                'guest_email' => $data['guest_email'] ?? null,
                'event_type' => $data['event_type'] ?? null,
                'guest_count' => $quote->guestCount,
                'notes' => $data['notes'] ?? null,
                'admin_notes' => $data['admin_notes'] ?? null,
                'starts_at' => $window['starts_at'],
                'ends_at' => $window['ends_at'],
                'total_amount_cents' => $total,
                'downpayment_required_cents' => $downpayment,
                'status' => BookingStatus::Pending,
                'source' => $data['source'] ?? BookingSource::Guest,
                'created_by' => $data['created_by'] ?? null,
                'original_total_cents' => $override === null ? null : $quote->totalCents,
                'price_override_reason' => $override,
            ]);

            $this->snapshotAddOns($booking, $quote);

            $this->logger->log(ActivityAction::BookingCreated, $booking, [
                'reference' => $booking->reference_code,
                'package' => $package->code,
                'starts_at' => $booking->starts_at->toDateTimeString(),
                'total_cents' => $total,
                'source' => $booking->source->value,
            ], $actor);

            if ($override !== null) {
                $this->logger->log(ActivityAction::BookingPriceOverridden, $booking, [
                    'from' => $quote->totalCents,
                    'to' => $total,
                    'reason' => $override,
                ], $actor);
            }

            return $booking;
        }));
    }

    /**
     * Moves a booking to another status, validated against TRANSITIONS.
     * Rejection needs a reason; approval stamps approved_by/approved_at; completion is only
     * possible once the stay has ended. Logs the change and fires BookingStatusChanged after commit.
     *
     * @param  User|null  $actor  Admin making the change (null = system)
     * @param  string|null  $reason  Required for rejections; optional note otherwise
     *
     * @throws InvalidStatusTransitionException When the change is not allowed or its preconditions fail
     */
    public function transition(Booking $booking, BookingStatus $to, ?User $actor, ?string $reason = null): Booking
    {
        return $this->applyTransition($booking, $to, $actor, $reason, ActivityAction::BookingStatusChanged);
    }

    /**
     * Whether TRANSITIONS allows moving the booking to $to (ignores time preconditions).
     */
    public function canTransition(Booking $booking, BookingStatus $to): bool
    {
        return in_array($to, self::TRANSITIONS[$booking->status->value], true);
    }

    /**
     * Moves a pending/approved booking to another date (and optionally another package).
     * The price snapshot is kept unless $reprice is true, in which case the booking is
     * re-quoted with its current add-ons and guest count at today's prices.
     *
     * @param  CarbonInterface  $newDate  New start date
     * @param  User|null  $actor  Admin rescheduling
     * @param  Package|null  $newPackage  Switch package (default: keep)
     * @param  bool  $reprice  Recalculate totals and add-on unit prices
     *
     * @throws InvalidStatusTransitionException When the booking is not pending/approved
     * @throws PackageUnavailableException When the new package is inactive
     * @throws SlotUnavailableException When the new window is taken or blocked
     * @throws GuestCountExceededException When the guest count does not fit the (new) package
     * @throws InvalidAddOnException When repricing and an add-on is no longer available
     */
    public function reschedule(Booking $booking, CarbonInterface $newDate, ?User $actor, ?Package $newPackage = null, bool $reprice = false): Booking
    {
        if (! in_array($booking->status, BookingStatus::blocking(), true)) {
            throw new InvalidStatusTransitionException("Only pending or approved bookings can be rescheduled ({$booking->reference_code} is {$booking->status->label()}).");
        }

        return $this->guardOverlap(fn (): Booking => DB::transaction(function () use ($booking, $newDate, $actor, $newPackage, $reprice): Booking {
            $this->lock();

            $package = $newPackage ?? $booking->package;
            if ($newPackage !== null && ! $newPackage->is_active) {
                throw new PackageUnavailableException('This package is not available for booking.');
            }

            $date = $this->day($newDate);
            $window = $this->availability->resolveWindow($package, $date);
            $this->ensureAvailable($window['starts_at'], $window['ends_at'], $booking->id);

            $addOns = BookingAddOn::query()
                ->where('booking_id', $booking->id)
                ->pluck('quantity', 'add_on_id')
                ->map(fn ($quantity): int => (int) $quantity)
                ->all();
            // Always validates the guest count against the (new) package; totals only change when repricing.
            $quote = $this->pricing->quote($package, $date, $reprice ? $addOns : [], $booking->guest_count);

            $from = ['package_id' => $booking->package_id, 'starts_at' => $booking->starts_at->toDateTimeString(), 'total_cents' => $booking->total_amount_cents];

            $booking->fill([
                'package_id' => $package->id,
                'starts_at' => $window['starts_at'],
                'ends_at' => $window['ends_at'],
            ]);

            if ($reprice) {
                $booking->fill([
                    'total_amount_cents' => $quote->totalCents,
                    'downpayment_required_cents' => $quote->downpaymentRequiredCents,
                ]);
            }

            $booking->save();

            if ($reprice) {
                BookingAddOn::query()->where('booking_id', $booking->id)->delete();
                $this->snapshotAddOns($booking, $quote);
            }

            $this->logger->log(ActivityAction::BookingRescheduled, $booking, [
                'from' => $from,
                'to' => ['package_id' => $booking->package_id, 'starts_at' => $booking->starts_at->toDateTimeString(), 'total_cents' => $booking->total_amount_cents],
                'repriced' => $reprice,
            ], $actor);

            return $booking->refresh();
        }));
    }

    /**
     * Cancels pending bookings older than booking.pending_hold_hours that have no payment proof,
     * freeing their slots (PLAN.md §5.3). Each one is logged as booking.expired.
     *
     * @param  CarbonInterface|null  $now  Reference time (default: now)
     * @param  bool  $dryRun  Only count, change nothing
     * @return int Number of bookings expired (or that would be)
     */
    public function expireStale(?CarbonInterface $now = null, bool $dryRun = false): int
    {
        $hours = max(1, $this->settings->int('booking.pending_hold_hours', 24));
        $cutoff = CarbonImmutable::instance($now ?? now())->subHours($hours);

        $stale = Booking::query()
            ->status(BookingStatus::Pending)
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('payments', fn ($query) => $query->whereNotNull('proof_path'))
            ->orderBy('id')
            ->get();

        if ($dryRun) {
            return $stale->count();
        }

        foreach ($stale as $booking) {
            $this->applyTransition($booking, BookingStatus::Cancelled, null, "Payment proof not received within {$hours} hours.", ActivityAction::BookingExpired);
        }

        return $stale->count();
    }

    /**
     * Shared transition logic (validation, persistence, audit, event).
     *
     * @throws InvalidStatusTransitionException
     */
    private function applyTransition(Booking $booking, BookingStatus $to, ?User $actor, ?string $reason, ActivityAction $action): Booking
    {
        $from = $booking->status;
        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;

        if (! $this->canTransition($booking, $to)) {
            throw new InvalidStatusTransitionException("A {$from->label()} booking cannot be changed to {$to->label()}.");
        }

        if ($to === BookingStatus::Rejected && $reason === null) {
            throw new InvalidStatusTransitionException('A reason is required to reject a booking.');
        }

        if ($to === BookingStatus::Completed && $booking->ends_at->isFuture()) {
            throw new InvalidStatusTransitionException('A booking can only be completed after the stay has ended.');
        }

        DB::transaction(function () use ($booking, $from, $to, $actor, $reason, $action): void {
            $booking->status = $to;

            if ($to === BookingStatus::Rejected) {
                $booking->rejection_reason = $reason;
            }

            if ($to === BookingStatus::Cancelled && $reason !== null) {
                $booking->cancellation_reason = $reason;
            }

            if ($to === BookingStatus::Approved) {
                $booking->approved_by = $actor?->id;
                $booking->approved_at = now();
            }

            $booking->save();

            $this->logger->log($action, $booking, array_filter([
                'from' => $from->value,
                'to' => $to->value,
                'reason' => $reason,
            ], fn ($value): bool => $value !== null), $actor);
        });

        // Dispatched after the transaction above has committed (or its savepoint released).
        BookingStatusChanged::dispatch($booking, $from, $to, $actor, $reason);

        return $booking;
    }

    /**
     * Total, downpayment and override reason after an optional owner price override (D-031).
     *
     * @param  array<string, mixed>  $data  create() input
     * @return array{0: int, 1: int, 2: string|null} [total cents, downpayment cents, override reason or null]
     *
     * @throws PriceOverrideNotAllowedException
     */
    private function applyOverride(PriceBreakdown $quote, array $data, ?User $actor): array
    {
        if (! isset($data['price_override_cents'])) {
            return [$quote->totalCents, $quote->downpaymentRequiredCents, null];
        }

        $override = (int) $data['price_override_cents'];
        $reason = trim((string) ($data['price_override_reason'] ?? ''));

        if ($actor === null || ! $actor->isOwner()) {
            throw new PriceOverrideNotAllowedException('Only the owner can override a booking price.');
        }

        if ($reason === '') {
            throw new PriceOverrideNotAllowedException('A reason is required to override the price.');
        }

        if ($override < 0) {
            throw new PriceOverrideNotAllowedException('The overridden price cannot be negative.');
        }

        return [$override, intdiv($override * $quote->downpaymentPercent + 50, 100), $reason];
    }

    /**
     * Takes the booking advisory lock until the surrounding transaction ends.
     */
    private function lock(): void
    {
        DB::select('SELECT pg_advisory_xact_lock(?)', [self::BOOKING_LOCK_KEY]);
    }

    /**
     * @param  int|null  $ignoreBookingId  Booking being moved
     *
     * @throws SlotUnavailableException
     */
    private function ensureAvailable(CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): void
    {
        if (! $this->availability->isAvailable($start, $end, $ignoreBookingId)) {
            throw new SlotUnavailableException('Sorry, the resort is already booked or closed from '.$start->format('M j, Y g:i A').' to '.$end->format('M j, Y g:i A').'.');
        }
    }

    /**
     * Stores the quoted add-on lines with their unit prices (snapshot).
     */
    private function snapshotAddOns(Booking $booking, PriceBreakdown $quote): void
    {
        foreach ($quote->addOns as $line) {
            BookingAddOn::query()->create([
                'booking_id' => $booking->id,
                'add_on_id' => $line['add_on_id'],
                'quantity' => $line['quantity'],
                'unit_price_cents' => $line['unit_price_cents'],
            ]);
        }
    }

    /**
     * Runs a write and turns an exclusion-constraint violation into SlotUnavailableException.
     *
     * @template T
     *
     * @param  Closure(): T  $write
     * @return T
     *
     * @throws SlotUnavailableException
     */
    private function guardOverlap(Closure $write): mixed
    {
        try {
            return $write();
        } catch (QueryException $e) {
            if ($e->getCode() === self::EXCLUSION_VIOLATION) {
                throw new SlotUnavailableException('Sorry, that time was just booked by someone else.', 0, $e);
            }

            throw $e;
        }
    }

    /**
     * Normalizes a date input to the start of that day in the app timezone.
     */
    private function day(string|CarbonInterface $date): CarbonImmutable
    {
        $value = $date instanceof CarbonInterface ? $date->format('Y-m-d') : $date;

        return CarbonImmutable::parse($value, config('app.timezone'))->startOfDay();
    }
}
