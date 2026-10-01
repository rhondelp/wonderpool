@extends('layouts.admin')

@section('title', 'Pricing rules')

@section('content')
    <x-admin.page-header title="Pricing rules" description="Weekend, holiday and season adjustments. Matching rules apply in priority order (lowest first), each on the running price.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.pricing-rules.create')" icon="plus">Add rule</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-ui.card title="Quote check" class="mb-6">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <x-ui.select name="quote_package" id="quote_package" label="Package" :options="$packages->mapWithKeys(fn ($p) => [$p->id => $p->name])->all()" :selected="(string) request('quote_package')" placeholder="Choose a package" />
            </div>
            <div class="flex-1">
                <x-ui.input name="quote_date" id="quote_date" type="date" label="Date" :value="request('quote_date')" />
            </div>
            <x-ui.button type="submit" variant="secondary" icon="calculator">Check price</x-ui.button>
        </form>

        @if ($quote)
            <dl class="mt-5 divide-y divide-slate-100 text-sm" aria-label="Price breakdown">
                <div class="flex justify-between py-2"><dt>{{ $quotePackage->name }} base price</dt><dd>{{ \App\Support\Money::format($quote->basePriceCents) }}</dd></div>
                @forelse ($quote->adjustments as $adjustment)
                    <div class="flex justify-between py-2 text-slate-700">
                        <dt>{{ $adjustment['label'] }}</dt>
                        <dd>{{ $adjustment['delta_cents'] >= 0 ? '+' : '' }}{{ \App\Support\Money::format($adjustment['delta_cents']) }} → {{ \App\Support\Money::format($adjustment['running_cents']) }}</dd>
                    </div>
                @empty
                    <div class="py-2 text-slate-500">No rules apply on {{ \Illuminate\Support\Carbon::parse($quote->date)->format('l, M j, Y') }}.</div>
                @endforelse
                <div class="flex justify-between py-2 font-semibold text-slate-900"><dt>Total</dt><dd>{{ \App\Support\Money::format($quote->totalCents) }}</dd></div>
                <div class="flex justify-between py-2 text-slate-600"><dt>Downpayment ({{ $quote->downpaymentPercent }}%)</dt><dd>{{ \App\Support\Money::format($quote->downpaymentRequiredCents) }}</dd></div>
            </dl>
        @endif
    </x-ui.card>

    <x-admin.index-filters placeholder="Search rule names" />

    @if ($rules->isEmpty())
        <x-ui.empty-state title="No pricing rules" :description="request()->hasAny(['q', 'status']) ? 'Try a different search or filter.' : 'Every booking uses the package base price.'" icon="receipt-percent">
            <x-ui.button :href="route('admin.pricing-rules.create')" icon="plus" class="mt-4">Add rule</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.card :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Priority</th>
                            <th scope="col" class="px-5 py-3">Rule</th>
                            <th scope="col" class="px-5 py-3">When</th>
                            <th scope="col" class="px-5 py-3">Sample effect</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rules as $rule)
                            <tr>
                                <td class="px-5 py-3 text-slate-600">{{ $rule->priority }}</td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-900">{{ $pricing->ruleLabel($rule) }}</p>
                                    <p class="text-xs text-slate-500">{{ $rule->type->label() }} · {{ $rule->package?->name ?? 'All packages' }}</p>
                                </td>
                                <td class="px-5 py-3 text-slate-600">
                                    @if ($rule->starts_on || $rule->ends_on)
                                        {{ $rule->starts_on?->format('M j, Y') ?? 'Any' }} – {{ $rule->ends_on?->format('M j, Y') ?? 'Any' }}
                                    @endif
                                    @if ($rule->days_of_week)
                                        <span class="block text-xs">{{ collect($rule->days_of_week)->map(fn ($d) => [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'][(int) $d] ?? $d)->implode(', ') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    @isset($samples[$rule->id])
                                        <span class="text-slate-600">{{ $samples[$rule->id]['package']->name }}:</span>
                                        {{ \App\Support\Money::format($samples[$rule->id]['before']) }} → <strong class="font-semibold text-slate-900">{{ \App\Support\Money::format($samples[$rule->id]['after']) }}</strong>
                                    @endisset
                                </td>
                                <td class="px-5 py-3">
                                    <x-ui.badge :color="$rule->is_active ? 'garden' : 'slate'">{{ $rule->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <x-ui.button :href="route('admin.pricing-rules.edit', $rule)" variant="secondary" size="sm" icon="pencil-square">Edit<span class="sr-only"> {{ $rule->name }}</span></x-ui.button>
                                        <x-admin.confirm-delete :action="route('admin.pricing-rules.destroy', $rule)" :label="$rule->name" :name="'delete-rule-'.$rule->id" warning="Existing bookings keep the price they were booked at." />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($rules->hasPages())
                <x-slot:footer>{{ $rules->links() }}</x-slot:footer>
            @endif
        </x-ui.card>
    @endif
@endsection
