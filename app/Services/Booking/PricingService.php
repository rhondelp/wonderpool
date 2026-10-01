<?php

namespace App\Services\Booking;

use App\Enums\PricingAdjustmentType;
use App\Exceptions\Booking\GuestCountExceededException;
use App\Exceptions\Booking\InvalidAddOnException;
use App\Models\AddOn;
use App\Models\Package;
use App\Models\PricingRule;
use App\Services\SettingService;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Quotes a booking (PLAN.md §5.4, D-022):
 * base price → every matching active pricing rule in priority order (lowest number first,
 * ties by id), each applied to the running package price (percentages compound) → + add-ons.
 * Add-ons are never adjusted by rules. Integer centavos only (D-001).
 */
class PricingService
{
    /** Largest quantity accepted per add-on. */
    public const MAX_ADD_ON_QUANTITY = 99;

    /**
     * @param  SettingService  $settings  Downpayment percentage
     */
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * Full price breakdown for a package on a date.
     *
     * @param  Package  $package  Package being booked
     * @param  CarbonInterface  $date  Booking start date (rules match on this date)
     * @param  array<int|string, int|string>  $addOns  [add_on_id => quantity]
     * @param  int  $guestCount  Number of guests (1..package max_pax)
     *
     * @throws GuestCountExceededException When the guest count is below 1 or above max_pax
     * @throws InvalidAddOnException When an add-on is unknown, inactive or has a bad quantity
     */
    public function quote(Package $package, CarbonInterface $date, array $addOns = [], int $guestCount = 1): PriceBreakdown
    {
        if ($guestCount < 1 || $guestCount > $package->max_pax) {
            throw new GuestCountExceededException("{$package->name} allows 1 to {$package->max_pax} guests; {$guestCount} requested.");
        }

        $running = $package->base_price_cents;
        $adjustments = [];

        foreach ($this->matchingRules($package, $date) as $rule) {
            $next = $this->applyRule($running, $rule);
            $adjustments[] = [
                'rule_id' => $rule->id,
                'label' => $this->ruleLabel($rule),
                'delta_cents' => $next - $running,
                'running_cents' => $next,
            ];
            $running = $next;
        }

        $lines = $this->addOnLines($addOns);
        $addOnsTotal = array_sum(array_column($lines, 'line_total_cents'));
        $total = $running + $addOnsTotal;
        $percent = max(0, min(100, $this->settings->int('booking.downpayment_percent', 50)));

        return new PriceBreakdown(
            packageId: $package->id,
            date: $date->format('Y-m-d'),
            guestCount: $guestCount,
            basePriceCents: $package->base_price_cents,
            adjustments: $adjustments,
            packageSubtotalCents: $running,
            addOns: $lines,
            addOnsTotalCents: $addOnsTotal,
            totalCents: $total,
            downpaymentPercent: $percent,
            downpaymentRequiredCents: intdiv($total * $percent + 50, 100),
        );
    }

    /**
     * Active rules that apply to the package on the date, in application order.
     * A rule matches when it is global or for this package, the date is within
     * [starts_on, ends_on] (null = open-ended) and, if days_of_week is set, the ISO
     * weekday (1 = Mon … 7 = Sun) is listed. Order: priority ascending, then id.
     *
     * @return Collection<int, PricingRule>
     */
    public function matchingRules(Package $package, CarbonInterface $date): Collection
    {
        $day = $date->format('Y-m-d');
        $weekday = (int) $date->format('N');

        return PricingRule::query()
            ->active()
            ->forPackage($package->id)
            ->where(fn (Builder $q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $day))
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $day))
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->filter(fn (PricingRule $rule): bool => $rule->days_of_week === null
                || $rule->days_of_week === []
                || in_array($weekday, array_map('intval', $rule->days_of_week), true))
            ->values();
    }

    /**
     * Applies one rule to an amount. Percent: whole points of the amount, rounded half away
     * from zero to the centavo. Fixed: signed centavos added. The result never goes below 0.
     *
     * @param  int  $amountCents  Running package price
     * @return int New running price in centavos
     */
    public function applyRule(int $amountCents, PricingRule $rule): int
    {
        $delta = match ($rule->adjustment_type) {
            PricingAdjustmentType::Percent => self::percentOf($amountCents, $rule->adjustment_value),
            PricingAdjustmentType::Fixed => $rule->adjustment_value,
        };

        return max(0, $amountCents + $delta);
    }

    /**
     * $percent % of $cents, rounded half away from zero to the centavo.
     */
    public static function percentOf(int $cents, int $percent): int
    {
        $product = $cents * $percent;

        return intdiv(abs($product) + 50, 100) * ($product <=> 0);
    }

    /**
     * Human label, e.g. "Weekend rate (+10%)" or "Summer season (+₱1,000.00)".
     */
    public function ruleLabel(PricingRule $rule): string
    {
        $value = $rule->adjustment_value;
        $amount = $rule->adjustment_type === PricingAdjustmentType::Percent
            ? ($value >= 0 ? '+' : '').$value.'%'
            : ($value >= 0 ? '+' : '').Money::format($value);

        return "{$rule->name} ({$amount})";
    }

    /**
     * Validated add-on lines priced at their current price.
     *
     * @param  array<int|string, int|string>  $addOns  [add_on_id => quantity]
     * @return list<array{add_on_id: int, name: string, quantity: int, unit_price_cents: int, line_total_cents: int}>
     *
     * @throws InvalidAddOnException
     */
    private function addOnLines(array $addOns): array
    {
        if ($addOns === []) {
            return [];
        }

        $models = AddOn::query()->whereIn('id', array_map('intval', array_keys($addOns)))->get()->keyBy('id');
        $lines = [];

        foreach ($addOns as $id => $quantity) {
            $addOn = $models->get((int) $id);
            $quantity = (int) $quantity;

            if ($addOn === null || ! $addOn->is_active) {
                throw new InvalidAddOnException('One of the selected add-ons is no longer available.');
            }

            if ($quantity < 1 || $quantity > self::MAX_ADD_ON_QUANTITY) {
                throw new InvalidAddOnException("Quantity for {$addOn->name} must be between 1 and ".self::MAX_ADD_ON_QUANTITY.'.');
            }

            $lines[] = [
                'add_on_id' => $addOn->id,
                'name' => $addOn->name,
                'quantity' => $quantity,
                'unit_price_cents' => $addOn->price_cents,
                'line_total_cents' => $addOn->price_cents * $quantity,
            ];
        }

        return $lines;
    }
}
