<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\PackageFactory;
use App\Models\Concerns\AdminListable;
use App\Models\Contracts\GuardsDeletion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bookable package (Day, Night, 24-Hour). Times are local wall-clock (Asia/Manila);
 * when crosses_midnight is true, end_time falls on the next calendar day.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property int $base_price_cents
 * @property string $start_time "HH:MM:SS"
 * @property string $end_time "HH:MM:SS"
 * @property bool $crosses_midnight
 * @property int $max_pax
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $formatted_price
 */
class Package extends Model implements GuardsDeletion
{
    use AdminListable;

    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'base_price_cents',
        'start_time',
        'end_time',
        'crosses_midnight',
        'max_pax',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price_cents' => 'integer',
            'crosses_midnight' => 'boolean',
            'max_pax' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Package-specific pricing rules (global rules have package_id NULL).
     *
     * @return HasMany<PricingRule, $this>
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    /**
     * Base price formatted as pesos, e.g. "₱7,000.00".
     *
     * @return Attribute<string, never>
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn (): string => Money::format($this->base_price_cents));
    }


    /**
     * Columns matched by the admin search box (ILIKE).
     *
     * @return list<string>
     */
    public static function adminSearchColumns(): array
    {
        return ['name', 'code', 'description'];
    }

    /**
     * Packages referenced by any booking (even soft-deleted ones) cannot be deleted; deactivate them instead.
     */
    public function deletionBlockedReason(): ?string
    {
        $count = $this->bookings()->withTrashed()->count();

        return $count === 0 ? null : "{$this->name} has {$count} booking(s) and cannot be deleted. Deactivate it instead to hide it from guests.";
    }

    /**
     * Packages that can be booked.
     *
     * @param  Builder<Package>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Admin-defined display order.
     *
     * @param  Builder<Package>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
