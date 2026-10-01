{{--
    Pricing-rule fields shared by create/edit, with a live sample-price preview (Alpine).
    The preview mirrors PricingService::applyRule() for this single rule; the index "Quote check"
    shows how all matching rules stack. To add a field: add it here, to PricingRuleRequest::rules()/payload(),
    a pricing_rules migration and PricingRule::$fillable.

    @param \App\Models\PricingRule $rule
    @param \Illuminate\Support\Collection<int, \App\Models\Package> $packages
    @param list<\App\Enums\PricingRuleType> $types
    @param list<\App\Enums\PricingAdjustmentType> $adjustmentTypes
--}}
@php
    $isPercent = ($rule->adjustment_type?->value ?? 'percent') === 'percent';
    $storedValue = $rule->adjustment_value === null ? null : ($isPercent ? $rule->adjustment_value : number_format(\App\Support\Money::toPesos($rule->adjustment_value), 2, '.', ''));
    $days = array_map('intval', (array) old('days_of_week', $rule->days_of_week ?? []));
    $samplePackages = $packages->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'cents' => $p->base_price_cents])->values();
    $weekdays = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
@endphp

<div
    class="space-y-5"
    x-data="{
        type: @js(old('type', $rule->type?->value)),
        adjustment: @js(old('adjustment_type', $rule->adjustment_type?->value ?? 'percent')),
        value: @js((string) old('adjustment_value', $storedValue ?? '')),
        packageId: @js((string) old('package_id', $rule->package_id ?? '')),
        packages: @js($samplePackages),
        get sample() { return this.packages.find(p => String(p.id) === this.packageId) ?? this.packages[0] ?? null },
        get delta() {
            if (!this.sample || this.value === '' || isNaN(Number(this.value))) return null;
            if (this.adjustment === 'percent') {
                const product = this.sample.cents * Math.trunc(Number(this.value));
                return Math.sign(product) * Math.floor((Math.abs(product) + 50) / 100);
            }
            return Math.round(Number(this.value) * 100);
        },
        get after() { return this.delta === null ? null : Math.max(0, this.sample.cents + this.delta) },
        peso(cents) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(cents / 100) },
    }"
>
    <x-ui.input name="name" label="Name" :value="$rule->name" hint="Shown in price breakdowns, e.g. Weekend rate, Holy Week, Summer season." required />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.select name="type" label="Type" :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :selected="$rule->type?->value" x-model="type" required />
        <x-ui.select name="package_id" label="Applies to" :options="['' => 'All packages'] + $packages->mapWithKeys(fn ($p) => [$p->id => $p->name])->all()" :selected="(string) $rule->package_id" x-model="packageId" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.input name="starts_on" type="date" label="From date" :value="$rule->starts_on?->format('Y-m-d')" hint="Required for holiday and season rules. Leave blank for 'any date'." />
        <x-ui.input name="ends_on" type="date" label="Until date (inclusive)" :value="$rule->ends_on?->format('Y-m-d')" hint="Holiday: leave blank for a single day." />
    </div>

    <fieldset>
        <legend class="mb-1 block text-sm font-medium text-slate-700">Days of the week <span class="font-normal text-slate-500">(required for weekend rules; blank = every day)</span></legend>
        <div class="flex flex-wrap gap-2">
            @foreach ($weekdays as $number => $label)
                <label class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-slate-300 has-[:checked]:bg-pool-50 has-[:checked]:text-pool-800 has-[:checked]:ring-pool-600">
                    <input type="checkbox" name="days_of_week[]" value="{{ $number }}" @checked(in_array($number, $days, true)) class="rounded border-slate-300 text-pool-700 focus:ring-pool-500">
                    {{ $label }}
                </label>
            @endforeach
        </div>
        @error('days_of_week')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
        @error('days_of_week.*')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </fieldset>

    <div class="grid gap-5 sm:grid-cols-3">
        <x-ui.select name="adjustment_type" label="Adjustment" :options="collect($adjustmentTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :selected="$rule->adjustment_type?->value" x-model="adjustment" required />
        <div>
            <x-ui.input name="adjustment_value" type="number" step="any" label="Value" :value="$storedValue" x-model="value" required />
            <p class="mt-1 text-sm text-slate-500" x-text="adjustment === 'percent' ? 'Whole percent, e.g. 10 or -15.' : 'Pesos, e.g. 1000 or -500.'"></p>
        </div>
        <x-ui.input name="priority" type="number" min="0" label="Priority" :value="$rule->priority" hint="Lower numbers apply first." required />
    </div>

    <x-ui.checkbox name="is_active" label="Active" :checked="$rule->is_active" />

    <div class="rounded-lg bg-pool-50 p-4 ring-1 ring-pool-200" aria-live="polite">
        <p class="text-xs font-semibold uppercase tracking-wide text-pool-800">Sample price</p>
        <template x-if="sample && after !== null">
            <p class="mt-1 text-sm text-slate-800">
                <span x-text="sample.name"></span>:
                <span x-text="peso(sample.cents)"></span> →
                <strong class="font-semibold" x-text="peso(after)"></strong>
                <span class="text-slate-600" x-text="'(' + (delta >= 0 ? '+' : '') + peso(delta) + ')'"></span>
            </p>
        </template>
        <p x-show="!sample || after === null" class="mt-1 text-sm text-slate-600">Enter a value to see its effect on a package's base price.</p>
        <p class="mt-2 text-xs text-slate-600">Other matching rules stack on top in priority order. Use "Quote check" on the list to see the full price for a date.</p>
    </div>
</div>
