{{--
    x-public.page-hero — Gradient banner with title for inner public pages, ending in a wave.

    @prop string      $title     Page title (h1)
    @prop string|null $subtitle  Supporting line

    Usage: <x-public.page-hero title="Amenities" subtitle="Everything the resort offers." />
--}}
@props([
    'title',
    'subtitle' => null,
])

<div class="relative bg-gradient-to-br from-pool-800 via-pool-700 to-garden-700 text-white">
    <div class="mx-auto max-w-7xl px-4 pb-16 pt-12 sm:px-6 sm:pb-20 sm:pt-16 lg:px-8">
        <h1 class="text-3xl font-semibold sm:text-4xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-3 max-w-2xl text-pool-50">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
    <x-ui.wave-divider class="absolute inset-x-0 bottom-0 text-white" />
</div>
