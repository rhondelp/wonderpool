<?php

namespace App\Services\Booking;

use App\Enums\ActivityAction;
use App\Enums\BookingStatus;
use App\Exceptions\Booking\BookingWindowException;
use App\Exceptions\Booking\GuestCountExceededException;
use App\Exceptions\Booking\InvalidAddOnException;
use App\Exceptions\Booking\PackageUnavailableException;
use App\Exceptions\Booking\ReferenceCodeExhaustedException;
use App\Exceptions\Booking\SlotUnavailableException;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Package;
use App\Services\SettingService;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;

/**
 * Guest-facing booking flow on top of the M4 engine (no pricing/availability logic of its own):
 * calendar data, live quotes, guest booking creation (+ lead time / max advance rule),
 * lookup by reference + phone (D-025), status timeline, and session-based access to a
 * guest's own booking pages.
 */
class GuestBookingService
{
    /** Session key holding ids of bookings this browser created or looked up. */
    public const SESSION_KEY = 'guest_bookings';

    /**
     * @param  AvailabilityService  $availability  Windows + overlap checks
     * @param  PricingService  $pricing  Quotes
     * @param  BookingService  $bookings  Creation
     * @param  SettingService  $settings  Payment instructions etc.
     * @param  Session  $session  Current session (booking page access)
     */
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly BookingService $bookings,
        private readonly SettingService $settings,
        private readonly Session $session,
    ) {
    }

    /**
     * Day states for one month of a package's calendar.
     * "available" = window free and bookable now; "booked" = overlaps a booking or block;
     * "closed" = outside the lead time / max advance window (incl. past days).
     *
     * @param  CarbonInterface  $month  Any day in the month
     * @return array{month: string, label: string, days: array<string, string>, has_previous: bool, has_next: bool}
     */
    public function calendar(Package $package, CarbonInterface $month): array
    {
        $first = CarbonImmutable::parse($month->format('Y-m-01'), config('app.timezone'));
        $last = $first->endOfMonth()->startOfDay();
        $map = $this->availability->unavailableDates($first, $last, $package);
        $days = [];

        foreach ($map as $date => $state) {
            $window = $this->availability->resolveWindow($package, CarbonImmutable::parse($date, config('app.timezone')));
            $days[$date] = match (true) {
                ! $this->availability->isWithinBookingWindow($window['starts_at']) => 'closed',
                ! $state['available'] => 'booked',
                default => 'available',
            };
        }

        $today = CarbonImmutable::now()->startOfMonth();
        $limit = CarbonImmutable::now()->addDays($this->settings->int('booking.max_advance_days', 365))->startOfMonth();

        return [
            'month' => $first->format('Y-m'),
            'label' => $first->format('F Y'),
            'days' => $days,
            'has_previous' => $first->greaterThan($today),
            'has_next' => $first->lessThan($limit),
        ];
    }

    /**
     * Live quote for the booking form: availability of the window plus the price breakdown.
     * Rule violations are returned as a friendly reason instead of thrown.
     *
     * @param  array<int|string, int|string>  $addOns  [add_on_id => quantity]
     * @return array{available: bool, reason: ?string, window: ?array{starts_at: string, ends_at: string, label: string}, breakdown: ?array<string, mixed>}
     */
    public function quote(Package $package, CarbonInterface $date, int $guestCount, array $addOns = []): array
    {
        $window = $this->availability->resolveWindow($package, $date);
        $windowInfo = [
            'starts_at' => $window['starts_at']->toIso8601String(),
            'ends_at' => $window['ends_at']->toIso8601String(),
            'label' => $this->windowLabel($window['starts_at'], $window['ends_at']),
        ];

        try {
            $breakdown = $this->pricing->quote($package, $date, $addOns, $guestCount)->toArray();
        } catch (GuestCountExceededException|InvalidAddOnException $e) {
            return ['available' => false, 'reason' => $e->getMessage(), 'window' => $windowInfo, 'breakdown' => null];
        }

        $reason = match (true) {
            ! $package->is_active => 'This package is not available for booking.',
            ! $this->availability->isWithinBookingWindow($window['starts_at']) => $this->bookingWindowMessage(),
            ! $this->availability->isAvailable($window['starts_at'], $window['ends_at']) => 'Sorry, the resort is already booked or closed at that time. Please pick another date or package.',
            default => null,
        };

        return ['available' => $reason === null, 'reason' => $reason, 'window' => $windowInfo, 'breakdown' => $breakdown];
    }

    /**
     * Creates a guest booking (lead time / max advance enforced here, not in BookingService)
     * and grants this browser access to its payment page.
     *
     * @param  array{package_id: int|string, date: string, guest_name: string, guest_phone: string, guest_email?: ?string, event_type?: ?string, guest_count: int|string, notes?: ?string, add_ons?: array<int|string, int|string>}  $data  Validated input
     *
     * @throws BookingWindowException When the date is too soon or too far ahead
     * @throws PackageUnavailableException When the package is missing or inactive
     * @throws SlotUnavailableException When the slot is taken or blocked
     * @throws GuestCountExceededException When the guest count does not fit
     * @throws InvalidAddOnException When an add-on is unavailable
     * @throws ReferenceCodeExhaustedException Rare reference code exhaustion
     */
    public function book(array $data): Booking
    {
        $package = Package::query()->find($data['package_id']);
        if ($package === null || ! $package->is_active) {
            throw new PackageUnavailableException('This package is not available for booking.');
        }

        $window = $this->availability->resolveWindow($package, CarbonImmutable::parse($data['date'], config('app.timezone')));
        if (! $this->availability->isWithinBookingWindow($window['starts_at'])) {
            throw new BookingWindowException($this->bookingWindowMessage());
        }

        $data['guest_phone'] = PhoneNumber::normalize($data['guest_phone']) ?? $data['guest_phone'];
        $booking = $this->bookings->create($data);
        $this->remember($booking);

        return $booking;
    }

    /**
     * Booking matching a reference code and phone number (both normalized), or null.
     *
     * @param  string  $reference  Any case/spacing, e.g. " wp-2610-a7k3 "
     * @param  string  $phone  Any PH mobile format
     */
    public function find(string $reference, string $phone): ?Booking
    {
        $code = self::normalizeReference($reference);
        $canonical = PhoneNumber::normalize($phone);

        if ($code === '' || $canonical === null) {
            return null;
        }

        return Booking::query()->where('reference_code', $code)->where('guest_phone', $canonical)->first();
    }

    /**
     * Reference code as stored: trimmed, inner spaces removed, uppercase.
     */
    public static function normalizeReference(string $reference): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', $reference) ?? '');
    }

    /**
     * Guest-safe status history built from the activity log (no admin notes or actor names).
     *
     * @return list<array{at: Carbon, label: string, note: ?string, tone: string}>
     */
    public function timeline(Booking $booking): array
    {
        $entries = ActivityLog::query()
            ->where('subject_type', $booking->getMorphClass())
            ->where('subject_id', $booking->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $timeline = [];
        foreach ($entries as $entry) {
            $props = $entry->properties ?? [];
            $item = match ($entry->action) {
                ActivityAction::BookingCreated->value => ['label' => 'Booking request received', 'note' => null, 'tone' => 'pool'],
                ActivityAction::PaymentProofUploaded->value => ['label' => 'Payment proof received', 'note' => ($props['replaced'] ?? false) ? 'Replaced the previous proof.' : null, 'tone' => 'pool'],
                ActivityAction::BookingRescheduled->value => ['label' => 'Booking moved', 'note' => isset($props['to']['starts_at']) ? 'New start: '.Carbon::parse($props['to']['starts_at'])->format('M j, Y g:i A') : null, 'tone' => 'amber'],
                ActivityAction::BookingStatusChanged->value, ActivityAction::BookingExpired->value => $this->statusEntry((string) ($props['to'] ?? ''), $props['reason'] ?? null),
                default => null,
            };

            if ($item !== null) {
                $timeline[] = ['at' => $entry->created_at] + $item;
            }
        }

        return $timeline;
    }

    /**
     * Lets this browser open the booking's payment/status pages (after creating or looking it up).
     */
    public function remember(Booking $booking): void
    {
        $ids = (array) $this->session->get(self::SESSION_KEY, []);
        $ids[] = $booking->id;
        $this->session->put(self::SESSION_KEY, array_values(array_unique(array_map('intval', $ids))));
    }

    /**
     * Whether this browser may open the booking's payment/status pages.
     */
    public function canAccess(Booking $booking): bool
    {
        return in_array($booking->id, array_map('intval', (array) $this->session->get(self::SESSION_KEY, [])), true);
    }

    /**
     * Payment instructions setting (shown on step 3 and the payment page).
     */
    public function paymentInstructions(): ?string
    {
        $text = trim((string) $this->settings->get('payment.instructions'));

        return $text === '' ? null : $text;
    }

    /**
     * Human window, e.g. "Sat, Jan 2, 2027, 7:00 PM – Sun, Jan 3, 5:00 AM".
     */
    public function windowLabel(CarbonInterface $start, CarbonInterface $end): string
    {
        $endFormat = $start->isSameDay($end) ? 'g:i A' : 'D, M j, g:i A';

        return $start->format('D, M j, Y, g:i A').' – '.$end->format($endFormat);
    }

    /**
     * Friendly lead-time / max-advance message.
     */
    private function bookingWindowMessage(): string
    {
        $hours = $this->settings->int('booking.lead_time_hours', 24);
        $days = $this->settings->int('booking.max_advance_days', 365);

        return "Online bookings must start at least {$hours} hours from now and at most {$days} days ahead. For urgent bookings, please call us.";
    }

    /**
     * Timeline entry for a status change.
     *
     * @return array{label: string, note: ?string, tone: string}
     */
    private function statusEntry(string $to, mixed $reason): array
    {
        $status = BookingStatus::tryFrom($to);

        return [
            'label' => match ($status) {
                BookingStatus::Approved => 'Booking approved',
                BookingStatus::Rejected => 'Booking rejected',
                BookingStatus::Cancelled => 'Booking cancelled',
                BookingStatus::Completed => 'Stay completed',
                default => 'Status updated',
            },
            'note' => is_string($reason) ? $reason : null,
            'tone' => $status?->color() ?? 'slate',
        ];
    }
}
