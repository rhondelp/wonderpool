{{--
    x-admin.occupancy-strip — Next N days at a glance (dashboard). Each day shows its state by color
    AND icon (never color alone), with a legend and a per-day text label for screen readers.

    @prop array  $strip  list of ['date' => CarbonImmutable, 'state' => confirmed|pending|closed|free]
                         (ReportService::occupancyStrip) (required)
    @prop string $title  Heading (default: "Next 30 days")

    Usage: <x-admin.occupancy-strip :strip="$strip" />
--}}
@props([
    'strip',
    'title' => 'Next 30 days',
])

@php
    $states = [
        'confirmed' => ['label' => 'Confirmed stay', 'cell' => 'bg-pool-700 text-white ring-pool-700', 'icon' => 'check-circle'],
        'pending' => ['label' => 'Pending request', 'cell' => 'bg-amber-50 text-amber-900 ring-amber-400', 'icon' => 'clock'],
        'closed' => ['label' => 'Blocked', 'cell' => 'bg-slate-200 text-slate-700 ring-slate-300', 'icon' => 'no-symbol'],
        'free' => ['label' => 'Open', 'cell' => 'bg-white text-slate-700 ring-slate-200', 'icon' => null],
    ];
    $totals = array_count_values(array_column($strip, 'state'));
@endphp

<section {{ $attributes->merge(['class' => 'rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200']) }} aria-labelledby="occupancy-title">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="occupancy-title" class="text-sm font-semibold text-slate-900">{{ $title }}</h2>
        <p class="text-sm text-slate-500">{{ $totals['confirmed'] ?? 0 }} of {{ count($strip) }} days confirmed</p>
    </div>

    <ol class="mt-4 grid grid-cols-7 gap-1.5 sm:grid-cols-10 lg:grid-cols-15">
        @foreach ($strip as $day)
            @php $state = $states[$day['state']]; @endphp
            <li class="flex flex-col items-center rounded-md px-1 py-1.5 text-center ring-1 {{ $state['cell'] }}" title="{{ $day['date']->format('D, M j') }}: {{ $state['label'] }}">
                <span class="text-[10px] uppercase leading-none opacity-80" aria-hidden="true">{{ $day['date']->format('D') }}</span>
                <span class="text-sm font-semibold leading-tight" aria-hidden="true">{{ $day['date']->format('j') }}</span>
                @if ($state['icon'])
                    <x-dynamic-component :component="'heroicon-m-'.$state['icon']" class="h-3.5 w-3.5" aria-hidden="true" />
                @else
                    <span class="h-3.5" aria-hidden="true"></span>
                @endif
                <span class="sr-only">{{ $day['date']->format('l, F j') }}: {{ $state['label'] }}</span>
            </li>
        @endforeach
    </ol>

    <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-600" aria-label="Legend">
        @foreach ($states as $key => $state)
            <li class="flex items-center gap-1.5">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded ring-1 {{ $state['cell'] }}" aria-hidden="true">
                    @if ($state['icon'])<x-dynamic-component :component="'heroicon-m-'.$state['icon']" class="h-3 w-3" />@endif
                </span>
                {{ $state['label'] }} ({{ $totals[$key] ?? 0 }})
            </li>
        @endforeach
    </ul>
</section>
