<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PricingAdjustmentType;
use App\Enums\PricingRuleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\StorePricingRuleRequest;
use App\Http\Requests\Admin\Content\UpdatePricingRuleRequest;
use App\Models\Package;
use App\Models\PricingRule;
use App\Services\Booking\PricingService;
use App\Services\Content\ContentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Owner-only seasonal / weekend / holiday pricing rules, with sample-price previews and a quote checker.
 */
class PricingRuleController extends Controller
{
    /**
     * Rules in application order, each with a sample effect, plus an optional quote check
     * (?quote_package=&quote_date=) showing how all matching rules stack.
     */
    public function index(Request $request, PricingService $pricing): View
    {
        Gate::authorize('viewAny', PricingRule::class);

        $packages = Package::query()->ordered()->get();
        $rules = PricingRule::query()
            ->with('package')
            ->search($request->string('q')->toString())
            ->whereState($request->string('status')->toString())
            ->orderBy('priority')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $samples = [];
        foreach ($rules as $rule) {
            $package = $rule->package ?? $packages->firstWhere('is_active', true) ?? $packages->first();
            if ($package !== null) {
                $samples[$rule->id] = ['package' => $package, 'before' => $package->base_price_cents, 'after' => $pricing->applyRule($package->base_price_cents, $rule)];
            }
        }

        $quote = null;
        $quotePackage = $packages->firstWhere('id', $request->integer('quote_package'));
        $quoteDate = $request->date('quote_date', 'Y-m-d');
        if ($quotePackage !== null && $quoteDate !== null) {
            $quote = $pricing->quote($quotePackage, CarbonImmutable::instance($quoteDate), [], 1);
        }

        return view('admin.pricing-rules.index', [
            'rules' => $rules,
            'samples' => $samples,
            'packages' => $packages,
            'quote' => $quote,
            'quotePackage' => $quotePackage,
            'pricing' => $pricing,
        ]);
    }

    /**
     * New-rule form.
     */
    public function create(): View
    {
        Gate::authorize('create', PricingRule::class);

        return view('admin.pricing-rules.create', $this->formData(new PricingRule([
            'type' => PricingRuleType::Weekend,
            'adjustment_type' => PricingAdjustmentType::Percent,
            'days_of_week' => [6, 7],
            'priority' => 10,
            'is_active' => true,
        ])));
    }

    /**
     * Saves a new rule.
     */
    public function store(StorePricingRuleRequest $request, ContentService $content): RedirectResponse
    {
        $rule = $content->create(PricingRule::class, $request->payload());

        return redirect()->route('admin.pricing-rules.index')->with('success', "{$rule->name} was added.");
    }

    /**
     * Edit form.
     */
    public function edit(PricingRule $pricingRule): View
    {
        Gate::authorize('update', $pricingRule);

        return view('admin.pricing-rules.edit', $this->formData($pricingRule));
    }

    /**
     * Saves changes (existing bookings keep their price snapshot).
     */
    public function update(UpdatePricingRuleRequest $request, PricingRule $pricingRule, ContentService $content): RedirectResponse
    {
        $content->update($pricingRule, $request->payload());

        return redirect()->route('admin.pricing-rules.index')->with('success', "{$pricingRule->name} was updated.");
    }

    /**
     * Deletes the rule.
     */
    public function destroy(PricingRule $pricingRule, ContentService $content): RedirectResponse
    {
        Gate::authorize('delete', $pricingRule);
        $content->delete($pricingRule);

        return redirect()->route('admin.pricing-rules.index')->with('success', "{$pricingRule->name} was deleted.");
    }

    /**
     * Shared form view data.
     *
     * @return array<string, mixed>
     */
    private function formData(PricingRule $rule): array
    {
        return [
            'rule' => $rule,
            'packages' => Package::query()->ordered()->get(),
            'types' => PricingRuleType::cases(),
            'adjustmentTypes' => PricingAdjustmentType::cases(),
        ];
    }
}
