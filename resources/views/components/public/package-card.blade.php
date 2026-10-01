{{--
    x-public.package-card — Package with hours, guest limit and base price, plus a "Book" link.

    @prop \App\Models\Package $package  Active package (required)

    Usage: <x-public.package-card :package="$package" />
--}}
@props([
    'package',
])

@php
    $start = \Illuminate\Support\Carbon::parse($package->start_time)->format('g:i A');
    $end = \Illuminate\Support\Carbon::parse($package->end_time)->format('g:i A');
@endphp

<article {{ $attributes->merge(['class' => 'flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md']) }}>
    <div class="flex items-start justify-between gap-3">
        <h3 class="text-lg font-semibold text-pool-900">{{ $package->name }}</h3>
        @if ($package->crosses_midnight)
            <x-ui.badge color="pool">Overnight</x-ui.badge>
        @endif
    </div>
    @if ($package->description)
        <p class="mt-2 text-sm text-slate-600">{{ $package->description }}</p>
    @endif

    <dl class="mt-4 space-y-2 text-sm text-slate-700">
        <div class="flex items-center gap-2">
            <x-heroicon-o-clock class="h-5 w-5 text-pool-600" aria-hidden="true" />
            <dt class="sr-only">Hours</dt>
            <dd>{{ $start }} – {{ $end }}@if ($package->crosses_midnight) <span class="text-slate-500">(next day)</span>@endif</dd>
        </div>
        <div class="flex items-center gap-2">
            <x-heroicon-o-users class="h-5 w-5 text-pool-600" aria-hidden="true" />
            <dt class="sr-only">Guests</dt>
            <dd>Up to {{ $package->max_pax }} guests</dd>
        </div>
    </dl>

    <div class="mt-auto flex items-end justify-between gap-3 pt-6">
        <p>
            <span class="block text-xs uppercase tracking-wide text-slate-500">From</span>
            <span class="text-2xl font-semibold text-pool-800">{{ $package->formatted_price }}</span>
        </p>
        <x-ui.button :href="route('book', ['package' => $package->id])" size="sm" icon="calendar-days">Book<span class="sr-only"> {{ $package->name }}</span></x-ui.button>
    </div>
</article>
