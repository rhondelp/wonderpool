{{--
    Home page. Every section hides itself when its content is empty (D-027).
    Sections: hero, highlights, about, amenities, packages, gallery strip, location, CTA.
    Data: PageController@home.
--}}
@extends('layouts.public')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-pool-800 via-pool-700 to-garden-700 text-white">
        <div class="mx-auto max-w-7xl px-4 pb-24 pt-16 sm:px-6 sm:pb-32 sm:pt-24 lg:px-8">
            <p class="text-sm font-medium uppercase tracking-widest text-pool-100">{{ $site['name'] }}</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-semibold leading-tight sm:text-5xl">{{ $heroTitle ?? $site['name'] }}</h1>
            @if ($heroSubtitle)
                <p class="mt-5 max-w-2xl text-lg text-pool-50">{{ $heroSubtitle }}</p>
            @endif
            <div class="mt-8 flex flex-wrap gap-3">
                <x-ui.button :href="route('book')" size="lg" variant="secondary" icon="calendar-days">Check availability</x-ui.button>
                <a href="{{ route('packages') }}" class="inline-flex items-center justify-center rounded-lg px-6 py-3 text-base font-medium text-white ring-1 ring-inset ring-white/60 transition-colors hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">See packages &amp; rates</a>
            </div>
        </div>
        <x-ui.wave-divider class="absolute inset-x-0 bottom-0 text-white" />
    </section>

    {{-- Highlights --}}
    @if ($highlights !== [])
        <section class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8" aria-label="Highlights">
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($highlights as $highlight)
                    <li class="flex items-start gap-3 rounded-2xl bg-gradient-to-br from-pool-50 to-garden-50 p-5 ring-1 ring-pool-100">
                        <x-heroicon-o-check-circle class="h-6 w-6 shrink-0 text-garden-700" aria-hidden="true" />
                        <span class="font-medium text-slate-800">{{ $highlight }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- About --}}
    <x-ui.section title="About the resort" :when="filled($about)">
        <x-ui.prose :text="$about" class="mx-auto max-w-3xl text-center" />
    </x-ui.section>

    {{-- Amenities --}}
    <x-ui.section title="Amenities" intro="Everything is yours for the whole booking: the resort is booked exclusively." :when="$amenities->isNotEmpty()" class="bg-gradient-to-b from-white to-pool-50">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($amenities as $amenity)
                <x-public.amenity-card :amenity="$amenity" />
            @endforeach
        </div>
        <div class="mt-8 text-center">
            <x-ui.button :href="route('amenities')" variant="secondary">All amenities</x-ui.button>
        </div>
    </x-ui.section>

    {{-- Packages --}}
    <x-ui.section title="Packages" intro="Pick a day, night or 24-hour stay. Weekend and holiday rates may apply." :when="$packages->isNotEmpty()">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($packages as $package)
                <x-public.package-card :package="$package" />
            @endforeach
        </div>
    </x-ui.section>

    {{-- Gallery strip --}}
    <x-ui.section title="Gallery" :when="$gallery->isNotEmpty()" class="bg-gradient-to-b from-pool-50 to-white">
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4" role="list">
            @foreach ($gallery as $image)
                <li>
                    <img src="{{ $image->imageUrl(\App\Enums\ImageVariant::Thumb) }}" alt="{{ $image->caption ?? $image->category->label() }}" width="480" height="360" loading="lazy" decoding="async" class="aspect-[4/3] w-full rounded-2xl object-cover shadow-sm">
                </li>
            @endforeach
        </ul>
        <div class="mt-8 text-center">
            <x-ui.button :href="route('gallery')" variant="secondary" icon="photo">View gallery</x-ui.button>
        </div>
    </x-ui.section>

    {{-- Location --}}
    <x-ui.section title="How to find us" :intro="$site['address']" :when="filled($site['map_embed_url']) || filled($site['address'])">
        @if ($site['map_embed_url'])
            <div class="overflow-hidden rounded-2xl shadow-sm ring-1 ring-slate-200">
                <iframe src="{{ $site['map_embed_url'] }}" title="Map to {{ $site['name'] }}" class="h-80 w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
        @endif
    </x-ui.section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-4 pb-6 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-gradient-to-r from-pool-700 to-garden-700 px-6 py-10 text-center text-white sm:px-12">
            <h2 class="text-2xl font-semibold sm:text-3xl">Ready for your pool day?</h2>
            <p class="mx-auto mt-3 max-w-xl text-pool-50">See which dates are free, get your price instantly and send your booking request in minutes.</p>
            <x-ui.button :href="route('book')" size="lg" variant="secondary" class="mt-6" icon="calendar-days">Book now</x-ui.button>
        </div>
    </section>
@endsection
