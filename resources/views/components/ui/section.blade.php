{{--
    x-ui.section — Public page section with optional heading and intro. Renders nothing when :when is false
    (use it to hide sections whose content is empty).

    @prop string|null $title  Heading (h2)
    @prop string|null $intro  Sub-text under the heading
    @prop string|null $id     Anchor id
    @prop bool        $when   Render the section at all (default: true)
    @slot default             Section body

    Usage: <x-ui.section title="Amenities" :when="$amenities->isNotEmpty()">…</x-ui.section>
--}}
@props([
    'title' => null,
    'intro' => null,
    'id' => null,
    'when' => true,
])

@if ($when)
    <section @if ($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => 'py-14 sm:py-20']) }}>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($title)
                <div class="mx-auto mb-10 max-w-2xl text-center">
                    <h2 class="text-2xl font-semibold text-pool-900 sm:text-3xl">{{ $title }}</h2>
                    @if ($intro)
                        <p class="mt-3 text-slate-600">{{ $intro }}</p>
                    @endif
                </div>
            @endif
            {{ $slot }}
        </div>
    </section>
@endif
