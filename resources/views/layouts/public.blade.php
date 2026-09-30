{{--
    Public site layout (guests). Mobile-first header with Alpine menu toggle, flash messages, footer.

    Sections: title, meta_description, content
    Stacks:   styles (in <head>), scripts (end of <body>)
    Nav links are placeholders until the public pages exist (M5).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    @include('partials.head')
</head>
<body class="flex min-h-full flex-col bg-slate-50 font-sans text-slate-800 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-pool-700">
        Skip to content
    </a>

    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8" aria-label="Main">
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-lg font-semibold text-pool-800">
                <x-heroicon-o-sun class="h-7 w-7 text-garden-500" aria-hidden="true" />
                <span>Wonderpool <span class="text-garden-600">Garden</span></span>
            </a>

            <div class="hidden items-center gap-6 text-sm font-medium md:flex">
                <a href="#" class="text-slate-600 hover:text-pool-700">Rooms &amp; Cottages</a>
                <a href="#" class="text-slate-600 hover:text-pool-700">Amenities</a>
                <a href="#" class="text-slate-600 hover:text-pool-700">Gallery</a>
                <a href="#" class="text-slate-600 hover:text-pool-700">Contact</a>
                <x-ui.button href="#" size="sm">Book now</x-ui.button>
            </div>

            <button type="button" x-on:click="open = !open" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 md:hidden" x-bind:aria-expanded="open" aria-controls="mobile-menu">
                <span class="sr-only">Toggle menu</span>
                <x-heroicon-o-bars-3 x-show="!open" class="h-6 w-6" aria-hidden="true" />
                <x-heroicon-o-x-mark x-show="open" x-cloak class="h-6 w-6" aria-hidden="true" />
            </button>
        </nav>

        <div id="mobile-menu" x-show="open" x-cloak x-transition class="border-t border-slate-200 px-4 pb-4 md:hidden">
            <div class="flex flex-col gap-1 pt-2 text-sm font-medium">
                <a href="#" class="rounded-md px-3 py-2 text-slate-700 hover:bg-pool-50">Rooms &amp; Cottages</a>
                <a href="#" class="rounded-md px-3 py-2 text-slate-700 hover:bg-pool-50">Amenities</a>
                <a href="#" class="rounded-md px-3 py-2 text-slate-700 hover:bg-pool-50">Gallery</a>
                <a href="#" class="rounded-md px-3 py-2 text-slate-700 hover:bg-pool-50">Contact</a>
                <x-ui.button href="#" class="mt-2 w-full">Book now</x-ui.button>
            </div>
        </div>
    </header>

    <main id="main" class="flex-1">
        <x-ui.flash class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8" />
        @yield('content')
    </main>

    <footer class="bg-pool-950 text-pool-100">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm sm:flex-row sm:justify-between sm:px-6 lg:px-8">
            <p>&copy; {{ now()->year }} {{ config('app.name') }}</p>
            <p class="text-pool-300">Pools · Gardens · Good times</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
