<?php

namespace Database\Factories;

use App\Enums\PricingAdjustmentType;
use App\Enums\PricingRuleType;
use App\Models\PricingRule;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingRule>
 */
class PricingRuleFactory extends Factory
{
    /**
     * Global weekend surcharge (+10%, Sat/Sun) by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => null,
            'name' => 'Weekend rate',
            'type' => PricingRuleType::Weekend,
            'starts_on' => null,
            'ends_on' => null,
            'days_of_week' => [6, 7],
            'adjustment_type' => PricingAdjustmentType::Percent,
            'adjustment_value' => 10,
            'priority' => 10,
            'is_active' => true,
        ];
    }

    /**
     * Summer season (Mar–May) fixed +₱1,000.
     */
    public function season(): static
    {
        return $this->state(fn () => [
            'name' => 'Summer season',
            'type' => PricingRuleType::Season,
            'starts_on' => now()->year.'-03-01',
            'ends_on' => now()->year.'-05-31',
            'days_of_week' => null,
            'adjustment_type' => PricingAdjustmentType::Fixed,
            'adjustment_value' => Money::fromPesos(1000),
            'priority' => 20,
        ]);
    }

    /**
     * Single-day holiday rule (+20%).
     */
    public function holiday(): static
    {
        return $this->state(function () {
            $date = fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d');

            return [
                'name' => 'Holiday rate',
                'type' => PricingRuleType::Holiday,
                'starts_on' => $date,
                'ends_on' => $date,
                'days_of_week' => null,
                'adjustment_type' => PricingAdjustmentType::Percent,
                'adjustment_value' => 20,
                'priority' => 30,
            ];
        });
    }
}
