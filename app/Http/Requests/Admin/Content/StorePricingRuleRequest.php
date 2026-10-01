<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\PricingRule;

/**
 * Create a pricing rule (rules in PricingRuleRequest).
 */
class StorePricingRuleRequest extends PricingRuleRequest
{
    /**
     * Delegates to PricingRulePolicy::create.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', PricingRule::class) ?? false;
    }
}
