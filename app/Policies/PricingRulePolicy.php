<?php

namespace App\Policies;

/**
 * PricingRule management: owner only (PLAN.md §2.2, D-016). Rules live in ContentPolicy.
 */
class PricingRulePolicy extends ContentPolicy
{
}
