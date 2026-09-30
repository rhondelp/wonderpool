<?php

namespace App\Models;

use App\Enums\PricingAdjustmentType;
use App\Enums\PricingRuleType;
use Database\Factories\PricingRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Price adjustment rule applied by PricingService (M4) in ascending priority order.
 * package_id NULL = applies to every package.
 *
 * @property int $id
 * @property int|null $package_id
 * @property string $name
 * @property PricingRuleType $type
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property list<int>|null $days_of_week ISO-8601 weekday numbers (1 = Mon … 7 = Sun)
 * @property PricingAdjustmentType $adjustment_type
 * @property int $adjustment_value Percent points or signed centavos, per adjustment_type
 * @property int $priority
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PricingRule extends Model
{
    /** @use HasFactory<PricingRuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'package_id',
        'name',
        'type',
        'starts_on',
        'ends_on',
        'days_of_week',
        'adjustment_type',
        'adjustment_value',
        'priority',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PricingRuleType::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'days_of_week' => 'array',
            'adjustment_type' => PricingAdjustmentType::class,
            'adjustment_value' => 'integer',
            'priority' => 'integer',
            'is_active' => 'boolean',
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
     * Rules currently enabled.
     *
     * @param  Builder<PricingRule>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Rules that apply to the given package (package-specific or global).
     *
     * @param  Builder<PricingRule>  $query
     */
    public function scopeForPackage(Builder $query, int $packageId): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('package_id')->orWhere('package_id', $packageId));
    }
}
