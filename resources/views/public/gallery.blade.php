{{--
    Gallery page with category filter and an accessible Alpine lightbox (focus trap, Esc, ← →).
    Data: PageController@gallery.
--}}
@extends('layouts.public')

@section('title', 'Gallery')

@php
    $items = $images->map(fn ($image) => [
        'id' => $image->id,
        'category' => $image->category->value,
        'thumb' => $image->imageUrl(\App\Enums\ImageVariant::Thumb),
        'large' => $image->imageUrl(\App\Enums\ImageVariant::Large),
        'alt' => $image->caption ?? $image->category->label(),
        'caption' => $image->caption,
    ])->values();
@endphp

@section('content')
    <x-public.page-hero title="Gallery" subtitle="A look around the pools, rooms, hall and past celebrations." />

    <x-ui.section>
        @if ($images->isEmpty())
            <x-ui.empty-state title="Photos coming soon" icon="photo" />
        @else
            <div
                x-data="{
                    filter: 'all',
                    items: @js($items),
                    current: null,
                    get visible() { return this.items.filter(i => this.filter === 'all' || i.category === this.filter) },
                    open(id) { this.current = this.visible.findIndex(i => i.id === id) },
                    close() { this.current = null },
                    step(delta) { const n = this.visible.length; this.current = (this.current + delta + n) % n },
                }"
                x-on:keydown.escape.window="close()"
                x-on:keydown.arrow-right.window="current !== null && step(1)"
                x-on:keydown.arrow-left.window="current !== null && step(-1)"
            >
                @if ($categories->count() > 1)
                    <div class="mb-8 flex flex-wrap justify-center gap-2" role="group" aria-label="Filter photos">
                        <button type="button" x-on:click="filter = 'all'" x-bind:aria-pressed="filter === 'all'" class="rounded-full px-4 py-2 text-sm font-medium ring-1 ring-pool-200 aria-pressed:bg-pool-700 aria-pressed:text-white aria-pressed:ring-pool-700">All</button>
                        @foreach ($categories as $category)
                            <button type="button" x-on:click="filter = @js($category->value)" x-bind:aria-pressed="filter === @js($category->value)" class="rounded-full px-4 py-2 text-sm font-medium ring-1 ring-pool-200 aria-pressed:bg-pool-700 aria-pressed:text-white aria-pressed:ring-pool-700">{{ $category->label() }}</button>
                        @endforeach
                    </div>
                @endif

                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" role="list">
                    @foreach ($images as $image)
                        <li x-show="filter === 'all' || filter === @js($image->category->value)">
                            <button type="button" x-on:click="open({{ $image->id }})" class="block w-full overflow-hidden rounded-2xl focus:outline-none focus-visible:ring-4 focus-visible:ring-pool-500">
                                <img src="{{ $image->imageUrl(\App\Enums\ImageVariant::Thumb) }}" alt="{{ $image->caption ?? $image->category->label() }}" width="480" height="360" loading="lazy" decoding="async" class="aspect-[4/3] w-full object-cover transition hover:scale-105">
                                <span class="sr-only">Open larger photo</span>
                            </button>
                        </li>
                    @endforeach
                </ul>

                {{-- Lightbox --}}
                <div x-show="current !== null" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4" role="dialog" aria-modal="true" aria-label="Photo viewer" x-trap.noscroll="current !== null" x-on:click.self="close()">
                    <template x-if="current !== null">
                        <figure class="max-h-full max-w-5xl">
                            <img x-bind:src="visible[current].large" x-bind:alt="visible[current].alt" class="max-h-[80vh] w-auto rounded-xl object-contain">
                            <figcaption class="mt-3 text-center text-sm text-slate-200" x-text="visible[current].caption ?? ''"></figcaption>
                        </figure>
                    </template>
                    <button type="button" x-on:click="close()" class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"><span class="sr-only">Close</span><x-heroicon-o-x-mark class="h-6 w-6" aria-hidden="true" /></button>
                    <button type="button" x-on:click="step(-1)" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 sm:left-6"><span class="sr-only">Previous photo</span><x-heroicon-o-chevron-left class="h-7 w-7" aria-hidden="true" /></button>
                    <button type="button" x-on:click="step(1)" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 sm:right-6"><span class="sr-only">Next photo</span><x-heroicon-o-chevron-right class="h-7 w-7" aria-hidden="true" /></button>
                </div>
            </div>
        @endif
    </x-ui.section>
@endsection
