<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\AddOnFactory;
use App\Models\Concerns\AdminListable;
use App\Models\Contracts\GuardsDeletion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Optional booking extra (extra hours, extra pax, cottage, videoke...).
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $price_cents
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $formatted_price
 */
class AddOn extends Model implements GuardsDeletion
{
    use AdminListable;

    /** @use HasFactory<AddOnFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'price_cents',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Bookings that include this add-on (pivot: quantity, unit_price_cents).
     *
     * @return BelongsToMany<Booking, $this, BookingAddOn, 'pivot'>
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_add_ons')
            ->using(BookingAddOn::class)
            ->withPivot(['id', 'quantity', 'unit_price_cents'])
            ->withTimestamps();
    }

    /**
     * Price formatted as pesos.
     *
     * @return Attribute<string, never>
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn (): string => Money::format($this->price_cents));
    }


    /**
     * Columns matched by the admin search box (ILIKE).
     *
     * @return list<string>
     */
    public static function adminSearchColumns(): array
    {
        return ['name', 'description'];
    }

    /**
     * Add-ons used on any booking cannot be deleted (booking_add_ons restricts); deactivate them instead.
     */
    public function deletionBlockedReason(): ?string
    {
        $count = BookingAddOn::query()->where('add_on_id', $this->getKey())->count();

        return $count === 0 ? null : "{$this->name} is used on {$count} booking(s) and cannot be deleted. Deactivate it instead.";
    }

    /**
     * Add-ons offered to guests.
     *
     * @param  Builder<AddOn>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
