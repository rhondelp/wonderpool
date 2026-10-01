<?php

namespace App\Enums;

/**
 * Kind of seasonal pricing rule (PLAN.md §4 pricing_rules.type).
 */
enum PricingRuleType: string
{
    case Weekend = 'weekend';
    case Holiday = 'holiday';
    case Season = 'season';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Weekend => 'Weekend / day of week',
            self::Holiday => 'Holiday',
            self::Season => 'Season (date range)',
        };
    }
}
