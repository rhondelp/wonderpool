{{--
    x-ui.badge — Pill label. Pass a booking/payment `status` to get its standard color (see HISTORY.md D-004),
    or a `color` for free-form badges. Label defaults to the humanized status.

    @prop string|null $status  Booking: pending|awaiting_payment|confirmed|checked_in|completed|cancelled|rejected|no_show
                               Payment: unpaid|partial|paid|refunded (optional)
    @prop string|null $color   pool|garden|amber|rose|slate — overrides the status color (optional)
    @slot default              Label (optional; falls back to the humanized status)

    Usage: <x-ui.badge status="confirmed" />  <x-ui.badge color="garden">Featured</x-ui.badge>
--}}
@props([
    'status' => null,
    'color' => null,
])

@php
    $statusColors = [
        'pending' => 'amber',
        'awaiting_payment' => 'amber',
        'confirmed' => 'pool',
        'checked_in' => 'garden',
        'completed' => 'slate',
        'cancelled' => 'rose',
        'rejected' => 'rose',
        'no_show' => 'slate',
        'unpaid' => 'rose',
        'partial' => 'amber',
        'paid' => 'garden',
        'refunded' => 'slate',
    ];

    $palette = [
        'pool' => 'bg-pool-100 text-pool-800 ring-pool-200',
        'garden' => 'bg-garden-100 text-garden-800 ring-garden-200',
        'amber' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'rose' => 'bg-rose-100 text-rose-800 ring-rose-200',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];

    $key = $color ?? ($statusColors[$status] ?? 'slate');
    $label = trim((string) $slot) !== '' ? $slot : \Illuminate\Support\Str::headline((string) $status);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset '.($palette[$key] ?? $palette['slate'])]) }}>
    {{ $label }}
</span>
