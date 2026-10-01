<?php

namespace App\Enums;

/**
 * How a pricing rule's adjustment_value is applied.
 *
 * Percent: whole percentage points added to the price (e.g. 10 = +10%, -15 = -15%).
 * Fixed:   signed amount in centavos added to the price (D-001), e.g. 100000 = +₱1,000.
 */
enum PricingAdjustmentType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }
}
