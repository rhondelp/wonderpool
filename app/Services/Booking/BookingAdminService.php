<?php

namespace App\Services\Booking;

use App\Enums\ActivityAction;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentState;
use App\Enums\PaymentType;
use App\Exceptions\Booking\BookingException;
use App\Exceptions\Booking\BookingWindowException;
use App\Exceptions\Booking\DownpaymentNotCoveredException;
use App\Exceptions\Booking\InvalidStatusTransitionException;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin booking workflow (M6). Orchestrates BookingService (transitions, rescheduling, creation)
 * and PaymentService; holds no pricing or availability logic of its own.
 * Approval rule (D-030): pending proofs are verified first; verified payments must then cover
 * the required downpayment, otherwise nothing is changed and DownpaymentNotCoveredException is thrown.
 */
class BookingAdminService
{
    /**
     * @param  BookingService  $bookings  Lifecycle engine
     * @param  PaymentService  $payments  Payments
     * @param  ActivityLogger  $logger  Audit trail
     * @param  AvailabilityService  $availability  Window resolution (walk-ins)
     */
    public function __construct(
        private readonly BookingService $bookings,
        private readonly PaymentService $payments,
        private readonly ActivityLogger $logger,
        private readonly AvailabilityService $availability,
    ) {
    }

    /**
     * Approves a pending booking (auto-verifying uploaded proofs first).
     *
     * @throws DownpaymentNotCoveredException When verified payments are below the downpayment
     * @throws InvalidStatusTransitionException When the booking is not pending
     */
    public function approve(Booking $booking, User $actor): Booking
    {
        if (! $this->bookings->canTransition($booking, BookingStatus::Approved)) {
            throw new InvalidStatusTransitionException("A {$booking->status->label()} booking cannot be approved.");
        }

        return DB::transaction(function () use ($booking, $actor): Booking {
            $this->payments->verifyPendingProofs($booking, $actor);
            $summary = $this->payments->summary($booking->unsetRelation('payments'));

            if (! $summary['downpayment_covered']) {
                $missing = $summary['downpayment_required'] - $summary['verified'];

                throw new DownpaymentNotCoveredException('The required downpayment is not covered yet ('.Money::format($missing).' still due). Record or verify a payment first.');
            }

            return $this->bookings->transition($booking, BookingStatus::Approved, $actor);
        });
    }

    /**
     * Rejects a pending booking with a reason shown to the guest.
     *
     * @throws InvalidStatusTransitionException
     */
    public function reject(Booking $booking, User $actor, string $reason): Booking
    {
        return $this->bookings->transition($booking, BookingStatus::Rejected, $actor, $reason);
    }

    /**
     * Cancels a pending/approved booking (frees the slot).
     *
     * @throws InvalidStatusTransitionException
     */
    public function cancel(Booking $booking, User $actor, ?string $reason): Booking
    {
        return $this->bookings->transition($booking, BookingStatus::Cancelled, $actor, $reason);
    }

    /**
     * Marks an approved booking as completed (after the stay).
     *
     * @throws InvalidStatusTransitionException
     */
    public function complete(Booking $booking, User $actor): Booking
    {
        return $this->bookings->transition($booking, BookingStatus::Completed, $actor);
    }

    /**
     * Moves the booking to another date/package (BookingService re-checks availability).
     *
     * @throws BookingException
     */
    public function reschedule(Booking $booking, CarbonInterface $date, User $actor, ?Package $package = null, bool $reprice = false): Booking
    {
        return $this->bookings->reschedule($booking, $date, $actor, $package, $reprice);
    }

    /**
     * Saves internal notes (never shown to guests).
     */
    public function updateNotes(Booking $booking, User $actor, ?string $notes): Booking
    {
        $notes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;

        if ($notes !== $booking->admin_notes) {
            $booking->forceFill(['admin_notes' => $notes])->save();
            $this->logger->log(ActivityAction::BookingNotesUpdated, $booking, [], $actor);
        }

        return $booking;
    }

    /**
     * Creates a walk-in/phone booking (source admin), optionally recording a payment and approving it,
     * all in one transaction. Lead time does not apply (a window may already have started, not ended);
     * the price override is owner-only (D-031).
     *
     * @param  array<string, mixed>  $data  BookingService::create() input plus optional payment_amount_cents, payment_type, payment_reference, approve
     *
     * @throws BookingException Any booking/payment rule violation (nothing is saved)
     */
    public function createWalkIn(array $data, User $actor): Booking
    {
        $package = Package::query()->find($data['package_id']);
        if ($package !== null) {
            $window = $this->availability->resolveWindow($package, Carbon::parse((string) $data['date'], config('app.timezone')));
            if ($window['ends_at']->isPast()) {
                throw new BookingWindowException('That time has already ended. Pick a current or future date.');
            }
        }

        return DB::transaction(function () use ($data, $actor): Booking {
            $booking = $this->bookings->create(array_merge($data, [
                'source' => BookingSource::Admin,
                'created_by' => $actor->id,
            ]), $actor);

            if (($data['payment_amount_cents'] ?? 0) > 0) {
                $this->payments->record(
                    $booking,
                    $data['payment_type'] ?? PaymentType::Downpayment,
                    (int) $data['payment_amount_cents'],
                    $actor,
                    $data['payment_reference'] ?? null,
                    'Recorded with walk-in booking.',
                );
            }

            if (! empty($data['approve'])) {
                $this->approve($booking, $actor);
            }

            return $booking->refresh();
        });
    }

    /**
     * Full admin timeline: every logged action on the booking with actor names.
     *
     * @return list<array{at: Carbon, action: string, label: string, actor: string, details: list<string>}>
     */
    public function timeline(Booking $booking): array
    {
        return ActivityLog::query()
            ->with('user')
            ->where('subject_type', $booking->getMorphClass())
            ->where('subject_id', $booking->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (ActivityLog $entry): array {
                $action = ActivityAction::tryFrom($entry->action);

                return [
                    'at' => $entry->created_at,
                    'action' => $entry->action,
                    'label' => $action?->label() ?? $entry->action,
                    'actor' => $entry->user_id !== null && $entry->user !== null
                        ? $entry->user->name
                        : ($entry->action === ActivityAction::BookingCreated->value ? 'Guest' : 'System'),
                    'details' => $this->details($entry->properties ?? []),
                ];
            })
            ->all();
    }

    /** Columns the index may sort by. */
    public const SORTABLE = ['starts_at', 'created_at', 'total_amount_cents', 'reference_code', 'guest_name'];

    /**
     * Filtered, sorted, paginated bookings for the admin index (D-033).
     *
     * @param  array{q?: ?string, status?: ?string, package?: int|string|null, payment?: ?string, from?: ?string, to?: ?string, sort?: ?string, dir?: ?string}  $filters
     * @return LengthAwarePaginator<int, Booking>
     */
    public function listing(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $status = BookingStatus::tryFrom((string) ($filters['status'] ?? ''));
        $payment = PaymentState::tryFrom((string) ($filters['payment'] ?? ''));
        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'starts_at';
        $direction = ($filters['dir'] ?? null) === 'asc' ? 'asc' : (($filters['dir'] ?? null) === 'desc' ? 'desc' : ($sort === 'starts_at' ? 'asc' : 'desc'));
        $from = $this->date($filters['from'] ?? null);
        $to = $this->date($filters['to'] ?? null);

        return Booking::query()
            ->with(['package', 'payments'])
            ->search($filters['q'] ?? null)
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->when(! empty($filters['package']), fn ($q) => $q->where('package_id', (int) $filters['package']))
            ->when($payment !== null, fn ($q) => $q->paymentState($payment))
            ->startingBetween($from, $to)
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Number of bookings per status (one GROUP BY query; soft-deleted excluded).
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = Booking::query()->toBase()->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status');

        $result = [];
        foreach (BookingStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    /**
     * Y-m-d filter value as a date, or null when blank/invalid.
     */
    private function date(?string $value): ?Carbon
    {
        if ($value === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return Carbon::parse($value, config('app.timezone'));
    }

    /**
     * Readable lines for a log entry's properties.
     *
     * @param  array<string, mixed>  $props
     * @return list<string>
     */
    private function details(array $props): array
    {
        $lines = [];

        if (isset($props['from'], $props['to']) && is_string($props['from']) && is_string($props['to'])) {
            $lines[] = ucfirst($props['from']).' → '.ucfirst($props['to']);
        }

        if (isset($props['from'], $props['to']) && is_int($props['from']) && is_int($props['to'])) {
            $lines[] = Money::format($props['from']).' → '.Money::format($props['to']);
        }

        if (isset($props['to']['starts_at']) && is_array($props['to'])) {
            $lines[] = 'New start: '.Carbon::parse($props['to']['starts_at'])->format('M j, Y g:i A').(($props['repriced'] ?? false) ? ' (repriced)' : '');
        }

        if (isset($props['amount_cents']) && is_int($props['amount_cents'])) {
            $lines[] = Money::format($props['amount_cents']).(isset($props['type']) ? ' ('.$props['type'].')' : '');
        }

        if (isset($props['reason']) && is_string($props['reason'])) {
            $lines[] = 'Reason: '.$props['reason'];
        }

        return $lines;
    }
}
