<?php

namespace App\Models;

use App\Enums\BookingStatus;
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
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
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
        ];
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
}
