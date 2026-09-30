{{--
    x-admin.stat-card — Dashboard KPI tile.

    @prop string      $label  Metric name, e.g. "Bookings today" (required)
    @prop mixed       $value  Display value, already formatted (required)
    @prop string|null $icon   Heroicon outline name (optional)
    @prop string      $color  pool|garden|amber|rose (icon accent, default: pool)
    @prop string|null $hint   Small caption under the value, e.g. "+12% vs last week" (optional)

    Usage: <x-admin.stat-card label="Pending" :value="$pending" icon="clock" color="amber" />
--}}
@props([
    'label',
    'value',
    'icon' => null,
    'color' => 'pool',
    'hint' => null,
])

@php
    $accents = [
        'pool' => 'bg-pool-50 text-pool-600',
        'garden' => 'bg-garden-50 text-garden-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200']) }}>
    @if ($icon)
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg {{ $accents[$color] ?? $accents['pool'] }}">
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" aria-hidden="true" />
        </div>
    @endif
    <div class="min-w-0">
        <p class="truncate text-sm font-medium text-slate-500">{{ $label }}</p>
        <p class="text-2xl font-semibold text-slate-900">{{ $value }}</p>
        @if ($hint)
            <p class="text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>
</div>
