<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentState;
use App\Enums\PaymentStatus;
use App\Support\PhoneNumber;
use App\Support\Money;
use Database\Factories\BookingFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A guest booking of the whole resort for the half-open window [starts_at, ends_at).
 * Amounts are snapshotted in centavos at booking time (D-001).
 *
 * @property int $id
 * @property string $reference_code
 * @property int $package_id
 * @property string $guest_name
 * @property string $guest_phone E.164, e.g. +639171234567
 * @property string|null $guest_email
 * @property string|null $event_type
 * @property int $guest_count
 * @property string|null $notes
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $total_amount_cents
 * @property int $downpayment_required_cents
 * @property BookingStatus $status
 * @property string|null $rejection_reason
 * @property string|null $admin_notes
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property BookingSource $source
 * @property int|null $created_by Admin who created a walk-in
 * @property int|null $original_total_cents Quoted total before an owner price override
 * @property string|null $price_override_reason
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $formatted_total
 * @property-read string $formatted_downpayment
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference_code',
        'package_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'event_type',
        'guest_count',
        'notes',
        'starts_at',
        'ends_at',
        'total_amount_cents',
        'downpayment_required_cents',
        'status',
        'rejection_reason',
        'admin_notes',
        'approved_by',
        'approved_at',
        'source',
        'created_by',
        'original_total_cents',
        'price_override_reason',
        'cancellation_reason',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'source' => 'guest',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'guest_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'total_amount_cents' => 'integer',
            'downpayment_required_cents' => 'integer',
            'status' => BookingStatus::class,
            'approved_at' => 'datetime',
            'source' => BookingSource::class,
            'original_total_cents' => 'integer',
        ];
    }

    /**
     * Stores the guest email trimmed and lowercase (PostgreSQL comparisons are case-sensitive).
     *
     * @return Attribute<string|null, string|null>
     */
    protected function guestEmail(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value === null ? null : mb_strtolower(trim($value)));
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Admin who approved the booking.
     *
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Admin who created a walk-in booking.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Selected add-ons (pivot: quantity, unit_price_cents).
     *
     * @return BelongsToMany<AddOn, $this, BookingAddOn, 'pivot'>
     */
    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'booking_add_ons')
            ->using(BookingAddOn::class)
            ->withPivot(['id', 'quantity', 'unit_price_cents'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Total formatted as pesos, e.g. "₱9,000.00".
     *
     * @return Attribute<string, never>
     */
    protected function formattedTotal(): Attribute
    {
        return Attribute::get(fn (): string => Money::format($this->total_amount_cents));
    }

    /**
     * Required downpayment formatted as pesos.
     *
     * @return Attribute<string, never>
     */
    protected function formattedDownpayment(): Attribute
    {
        return Attribute::get(fn (): string => Money::format($this->downpayment_required_cents));
    }

    /**
     * Bookings that hold the resort's time window (pending or approved; PLAN.md §5.1).
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::blocking());
    }

    /**
     * Bookings whose [starts_at, ends_at) window overlaps [$start, $end).
     * Touching boundaries (one ends exactly when the other starts) are NOT overlaps.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeOverlapping(Builder $query, DateTimeInterface $start, DateTimeInterface $end): void
    {
        $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    /**
     * Filter by one status.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeStatus(Builder $query, BookingStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Admin search (D-033): case-insensitive ILIKE on reference, guest name and phone;
     * a phone typed in any PH format also matches its normalized +639 form.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
        $phone = PhoneNumber::normalize($term);

        $query->where(function (Builder $q) use ($pattern, $phone): void {
            $q->whereAny(['reference_code', 'guest_name', 'guest_phone', 'guest_email'], 'ilike', $pattern);

            if ($phone !== null) {
                $q->orWhere('guest_phone', $phone);
            }
        });
    }

    /**
     * Bookings whose stay starts within [from, to] (inclusive dates; either may be null).
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeStartingBetween(Builder $query, ?DateTimeInterface $from, ?DateTimeInterface $to): void
    {
        $query
            ->when($from !== null, fn (Builder $q) => $q->where('starts_at', '>=', Carbon::instance($from)->startOfDay()))
            ->when($to !== null, fn (Builder $q) => $q->where('starts_at', '<', Carbon::instance($to)->startOfDay()->addDay()));
    }

    /**
     * Filter by derived payment state, same precedence as PaymentService::summary() (D-030).
     *
     * @param  Builder<Booking>  $query
     */
    public function scopePaymentState(Builder $query, PaymentState $state): void
    {
        $verified = '(SELECT COALESCE(SUM(p.amount_cents), 0) FROM payments p WHERE p.booking_id = bookings.id AND p.status = ?)';
        $pendingProof = 'EXISTS (SELECT 1 FROM payments p WHERE p.booking_id = bookings.id AND p.status = ? AND p.proof_path IS NOT NULL)';
        $v = PaymentStatus::Verified->value;
        $pending = PaymentStatus::Pending->value;

        match ($state) {
            PaymentState::Paid => $query->whereRaw("{$verified} >= bookings.total_amount_cents", [$v]),
            PaymentState::ProofPending => $query->whereRaw("{$verified} < bookings.total_amount_cents", [$v])->whereRaw($pendingProof, [$pending]),
            PaymentState::Partial => $query->whereRaw("{$verified} > 0", [$v])->whereRaw("{$verified} < bookings.total_amount_cents", [$v])->whereRaw("NOT {$pendingProof}", [$pending]),
            PaymentState::Unpaid => $query->whereRaw("{$verified} = 0", [$v])->whereRaw("NOT {$pendingProof}", [$pending]),
        };
    }
}
