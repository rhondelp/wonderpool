<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\BookingAddOnFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * Pivot row: an add-on attached to a booking, with the unit price snapshotted at booking time.
 *
 * @property int $id
 * @property int $booking_id
 * @property int $add_on_id
 * @property int $quantity
 * @property int $unit_price_cents
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $line_total_cents
 */
class BookingAddOn extends Pivot
{
    /** @use HasFactory<BookingAddOnFactory> */
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'booking_add_ons';

    /**
     * @var bool
     */
    public $incrementing = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'add_on_id',
        'quantity',
        'unit_price_cents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<AddOn, $this>
     */
    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class);
    }

    /**
     * quantity × unit price, in centavos.
     *
     * @return Attribute<int, never>
     */
    protected function lineTotalCents(): Attribute
    {
        return Attribute::get(fn (): int => $this->quantity * $this->unit_price_cents);
    }

    /**
     * Formats the line total as pesos.
     */
    public function formattedLineTotal(): string
    {
        return Money::format($this->line_total_cents);
    }
}
