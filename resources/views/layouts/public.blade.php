{{--
    Public site layout (guests). Mobile-first header with Alpine menu, flash messages, footer from settings.

    Sections: title, meta_description, og_image, content
    Stacks:   styles (in <head>), scripts (end of <body>)
    $site comes from the view composer (AppServiceProvider → PublicContentService::site()).
    Nav: edit $nav below.
--}}
@php
    $nav = [
        ['label' => 'Packages', 'route' => 'packages'],
        ['label' => 'Amenities', 'route' => 'amenities'],
        ['label' => 'Gallery', 'route' => 'gallery'],
        ['label' => 'FAQ', 'route' => 'faq'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    @include('partials.head')
</head>
<body class="flex min-h-full flex-col bg-white font-sans text-slate-800 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-pool-700">
        Skip to content
    </a>

    <header x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8" aria-label="Main">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-semibold text-pool-800">
                <x-heroicon-o-sun class="h-7 w-7 text-garden-600" aria-hidden="true" />
                <span>{{ $site['name'] }}</span>
            </a>

            <div class="hidden items-center gap-6 text-sm font-medium lg:flex">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" @class(['hover:text-pool-700', 'text-pool-800' => request()->routeIs($item['route']), 'text-slate-600' => ! request()->routeIs($item['route'])]) @if (request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ route('track') }}" class="text-slate-600 hover:text-pool-700">Track booking</a>
                <x-ui.button :href="route('book')" size="sm" icon="calendar-days">Book now</x-ui.button>
            </div>

            <button type="button" x-on:click="open = !open" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" x-bind:aria-expanded="open" aria-controls="mobile-menu">
                <span class="sr-only">Toggle menu</span>
                <x-heroicon-o-bars-3 x-show="!open" class="h-6 w-6" aria-hidden="true" />
                <x-heroicon-o-x-mark x-show="open" x-cloak class="h-6 w-6" aria-hidden="true" />
            </button>
        </nav>

        <div id="mobile-menu" x-show="open" x-cloak x-transition class="border-t border-slate-200 px-4 pb-4 lg:hidden">
            <div class="flex flex-col gap-1 pt-2 text-sm font-medium">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="rounded-md px-3 py-2 text-slate-700 hover:bg-pool-50">{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ route('track') }}" class="rounded-md px-3 py-2 text-slate-700 hover:bg-pool-50">Track booking</a>
                <x-ui.button :href="route('book')" class="mt-2 w-full" icon="calendar-days">Book now</x-ui.button>
            </div>
        </div>
    </header>

    <main id="main" class="flex-1">
        <x-ui.flash class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8" />
        @yield('content')
    </main>

    <footer class="relative mt-16 bg-pool-950 text-pool-100">
        <x-ui.wave-divider flip class="absolute inset-x-0 -top-10 text-pool-950 sm:-top-16" />
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 text-sm sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div>
                <p class="text-base font-semibold text-white">{{ $site['name'] }}</p>
                @if ($site['tagline'])
                    <p class="mt-2 text-pool-200">{{ $site['tagline'] }}</p>
                @endif
            </div>

            <div>
                <p class="font-semibold text-white">Visit</p>
                <ul class="mt-2 space-y-1">
                    <li><a href="{{ route('packages') }}" class="hover:text-white">Packages &amp; rates</a></li>
                    <li><a href="{{ route('policies') }}" class="hover:text-white">House rules &amp; policies</a></li>
                    <li><a href="{{ route('faq') }}" class="hover:text-white">FAQ</a></li>
                    <li><a href="{{ route('track') }}" class="hover:text-white">Track your booking</a></li>
                </ul>
            </div>

            <div>
                <p class="font-semibold text-white">Contact</p>
                <ul class="mt-2 space-y-1">
                    @if ($site['phone'])<li><a href="tel:{{ preg_replace('/[^\d+]/', '', $site['phone']) }}" class="hover:text-white">{{ $site['phone'] }}</a></li>@endif
                    @if ($site['email'])<li><a href="mailto:{{ $site['email'] }}" class="hover:text-white">{{ $site['email'] }}</a></li>@endif
                    @if ($site['address'])<li class="text-pool-200">{{ $site['address'] }}</li>@endif
                </ul>
            </div>

            @if ($site['facebook_url'] || $site['instagram_url'])
                <div>
                    <p class="font-semibold text-white">Follow us</p>
                    <ul class="mt-2 space-y-1">
                        @if ($site['facebook_url'])<li><a href="{{ $site['facebook_url'] }}" class="hover:text-white" rel="noopener" target="_blank">Facebook</a></li>@endif
                        @if ($site['instagram_url'])<li><a href="{{ $site['instagram_url'] }}" class="hover:text-white" rel="noopener" target="_blank">Instagram</a></li>@endif
                    </ul>
                </div>
            @endif
        </div>
        <div class="border-t border-pool-900">
            <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-4 text-xs text-pool-300 sm:flex-row sm:justify-between sm:px-6 lg:px-8">
                <p>&copy; {{ now()->year }} {{ $site['name'] }}</p>
                <p>Website by <a href="mailto:{{ config('wonderpool.developer.email') }}" class="font-medium text-pool-100 hover:text-white hover:underline">{{ config('wonderpool.developer.name') }}</a></p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
