{{--
    x-ui.badge — Pill label. Pass a status enum to use its label() and color() (PLAN.md §7, D-004),
    or a `color` for free-form badges.

    @prop \App\Enums\BookingStatus|\App\Enums\PaymentStatus|\App\Enums\UserRole|null $status  Enum with label()/color() (optional)
    @prop string|null $color  pool|garden|amber|rose|slate — overrides the status color (optional)
    @slot default             Label (optional; falls back to $status->label())

    Usage: <x-ui.badge :status="$booking->status" />  <x-ui.badge color="garden">Featured</x-ui.badge>
--}}
@props([
    'status' => null,
    'color' => null,
])

@php
    $palette = [
        'pool' => 'bg-pool-100 text-pool-800 ring-pool-200',
        'garden' => 'bg-garden-100 text-garden-800 ring-garden-200',
        'amber' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'rose' => 'bg-rose-100 text-rose-800 ring-rose-200',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];

    $key = $color ?? $status?->color() ?? 'slate';
    $label = trim((string) $slot) !== '' ? $slot : $status?->label();
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset '.($palette[$key] ?? $palette['slate'])]) }}>
    {{ $label }}
</span>
