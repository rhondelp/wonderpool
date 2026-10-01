{{--
    x-ui.wave-divider — Decorative water wave between sections (inline SVG, inherits text color).

    @prop bool $flip  Mirror vertically (wave hangs from the top) (default: false)

    Usage: <x-ui.wave-divider class="text-white" />  <x-ui.wave-divider flip class="text-pool-50" />
--}}
@props([
    'flip' => false,
])

<div aria-hidden="true" {{ $attributes->merge(['class' => 'pointer-events-none block w-full leading-none']) }}>
    <svg viewBox="0 0 1440 80" preserveAspectRatio="none" class="block h-10 w-full sm:h-16 {{ $flip ? 'rotate-180' : '' }}" fill="currentColor" focusable="false">
        <path d="M0 48c120-26 240-38 360-24s240 54 360 54 240-40 360-54 240-2 360 24v32H0z" opacity=".35" />
        <path d="M0 58c160-30 320-36 480-14s320 46 480 36 320-48 480-38v38H0z" />
    </svg>
</div>
