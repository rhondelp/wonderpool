{{--
    x-ui.alert — Inline status message with icon; optionally dismissible (Alpine).

    @prop string      $type         success|error|warning|info (default: info)
    @prop string|null $title        Bold heading line (optional)
    @prop bool        $dismissible  Show a close button (default: false)
    @slot default                   Message body

    Usage: <x-ui.alert type="success" dismissible>Booking saved.</x-ui.alert>
--}}
@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $styles = [
        'success' => ['box' => 'bg-garden-50 text-garden-800 ring-garden-200', 'icon' => 'check-circle'],
        'error' => ['box' => 'bg-rose-50 text-rose-800 ring-rose-200', 'icon' => 'x-circle'],
        'warning' => ['box' => 'bg-amber-50 text-amber-800 ring-amber-200', 'icon' => 'exclamation-triangle'],
        'info' => ['box' => 'bg-pool-50 text-pool-800 ring-pool-200', 'icon' => 'information-circle'],
    ];
    $style = $styles[$type] ?? $styles['info'];
@endphp

<div
    @if ($dismissible) x-data="{ show: true }" x-show="show" x-transition @endif
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => 'flex gap-3 rounded-lg p-4 text-sm ring-1 ring-inset '.$style['box']]) }}
>
    <x-dynamic-component :component="'heroicon-o-'.$style['icon']" class="h-5 w-5 shrink-0" aria-hidden="true" />

    <div class="flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-1' => $title])>{{ $slot }}</div>
    </div>

    @if ($dismissible)
        <button type="button" x-on:click="show = false" class="-m-1 shrink-0 rounded-md p-1 opacity-70 hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-current">
            <span class="sr-only">Dismiss</span>
            <x-heroicon-o-x-mark class="h-4 w-4" aria-hidden="true" />
        </button>
    @endif
</div>
