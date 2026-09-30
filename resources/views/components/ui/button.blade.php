{{--
    x-ui.button — Primary action element; renders <a> when `href` is set, otherwise <button>.

    @prop string      $variant  primary|secondary|danger|ghost (default: primary)
    @prop string      $size     sm|md|lg (default: md)
    @prop string      $type     Button type attribute when rendered as <button> (default: button)
    @prop string|null $href     If set, renders an anchor link instead of a button
    @prop string|null $icon     Heroicon outline name without prefix, e.g. "plus" (optional, leading)
    @slot default               Button label

    Usage: <x-ui.button variant="secondary" size="sm" icon="plus">Add room</x-ui.button>
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
])

@php
    $variants = [
        'primary' => 'bg-pool-600 text-white hover:bg-pool-700 focus-visible:ring-pool-500',
        'secondary' => 'bg-white text-pool-800 ring-1 ring-inset ring-pool-200 hover:bg-pool-50 focus-visible:ring-pool-500',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-500',
        'ghost' => 'bg-transparent text-pool-700 hover:bg-pool-50 focus-visible:ring-pool-500',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm gap-1.5',
        'md' => 'px-4 py-2 text-sm gap-2',
        'lg' => 'px-6 py-3 text-base gap-2',
    ];

    $iconSizes = ['sm' => 'h-4 w-4', 'md' => 'h-5 w-5', 'lg' => 'h-5 w-5'];

    $classes = 'inline-flex items-center justify-center rounded-lg font-medium transition-colors '
        .'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 '
        .'disabled:cursor-not-allowed disabled:opacity-50 '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="{{ $iconSizes[$size] ?? 'h-5 w-5' }}" aria-hidden="true" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="{{ $iconSizes[$size] ?? 'h-5 w-5' }}" aria-hidden="true" />
        @endif
        {{ $slot }}
    </button>
@endif
