<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\PricingRule;

/**
 * Edit a pricing rule (rules in PricingRuleRequest).
 */
class UpdatePricingRuleRequest extends PricingRuleRequest
{
    /**
     * Delegates to PricingRulePolicy::update.
     */
    public function authorize(): bool
    {
        /** @var PricingRule $pricing_rule */
        $pricing_rule = $this->route('pricing_rule');

        return $this->user()?->can('update', $pricing_rule) ?? false;
    }
}
