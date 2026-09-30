{{--
    Admin panel layout. Static sidebar on lg+, Alpine slide-in drawer on smaller screens.

    Sections: title, content
    Stacks:   styles (in <head>), scripts (end of <body>)
    Nav items are placeholders ('#') until their modules/routes exist (M2+); edit $nav below.
--}}
@php
    $nav = [
        ['label' => 'Dashboard', 'icon' => 'home', 'href' => '#'],
        ['label' => 'Bookings', 'icon' => 'calendar-days', 'href' => '#'],
        ['label' => 'Rooms & Cottages', 'icon' => 'building-office-2', 'href' => '#'],
        ['label' => 'Amenities', 'icon' => 'sparkles', 'href' => '#'],
        ['label' => 'Reports', 'icon' => 'chart-bar', 'href' => '#'],
        ['label' => 'Activity Logs', 'icon' => 'clipboard-document-list', 'href' => '#'],
        ['label' => 'Settings', 'icon' => 'cog-6-tooth', 'href' => '#'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased" x-data="{ sidebarOpen: false }" x-on:keydown.escape.window="sidebarOpen = false">
    {{-- Mobile drawer --}}
    <div x-show="sidebarOpen" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60" x-on:click="sidebarOpen = false"></div>
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
            x-trap.noscroll="sidebarOpen"
            class="fixed inset-y-0 left-0 flex w-72 flex-col bg-pool-950"
        >
            <button type="button" x-on:click="sidebarOpen = false" class="absolute right-3 top-4 rounded-md p-1 text-pool-200 hover:text-white">
                <span class="sr-only">Close sidebar</span>
                <x-heroicon-o-x-mark class="h-6 w-6" aria-hidden="true" />
            </button>
            @include('partials.admin-sidebar', ['nav' => $nav])
        </div>
    </div>

    {{-- Desktop sidebar --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col bg-pool-950">
        @include('partials.admin-sidebar', ['nav' => $nav])
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
            <button type="button" x-on:click="sidebarOpen = true" class="-ml-2 rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden">
                <span class="sr-only">Open sidebar</span>
                <x-heroicon-o-bars-3 class="h-6 w-6" aria-hidden="true" />
            </button>
            <p class="truncate text-sm font-medium text-slate-500">@yield('title', 'Admin')</p>
            <div class="ml-auto flex items-center gap-3 text-sm text-slate-600">
                <x-heroicon-o-user-circle class="h-7 w-7 text-pool-600" aria-hidden="true" />
                <span class="hidden sm:inline">{{ auth()->user()->name ?? 'Administrator' }}</span>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            <x-ui.flash class="mb-6" />
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
