{{--
    Admin panel layout. Static sidebar on lg+, Alpine slide-in drawer on smaller screens.

    Sections: title, content
    Stacks:   styles (in <head>), scripts (end of <body>)
    Nav: add a module by appending to $nav below (route = link target, active = routeIs pattern,
    can = gate/ability required, or null for every admin). Only built modules are listed.
--}}
@php
    $nav = [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'can' => null],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'can' => 'manage-users'],
        ['label' => 'Settings', 'icon' => 'cog-6-tooth', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'can' => 'manage-settings'],
    ];
    $nav = array_values(array_filter($nav, fn (array $item): bool => $item['can'] === null || (auth()->user()?->can($item['can']) ?? false)));
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
            <div class="relative ml-auto" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open" aria-haspopup="true" aria-controls="user-menu" class="flex items-center gap-2 rounded-lg p-1.5 text-sm text-slate-700 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-pool-500">
                    <x-heroicon-o-user-circle class="h-7 w-7 text-pool-700" aria-hidden="true" />
                    <span class="hidden sm:inline">{{ auth()->user()?->name }}</span>
                    <x-heroicon-m-chevron-down class="h-4 w-4 text-slate-500" aria-hidden="true" />
                </button>
                <div id="user-menu" x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-56 overflow-hidden rounded-lg bg-white py-1 shadow-lg ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 px-4 py-2">
                        <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()?->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ auth()->user()?->role->label() }}</p>
                    </div>
                    <a href="{{ route('admin.password.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-pool-50">
                        <x-heroicon-o-key class="h-4 w-4" aria-hidden="true" /> Change password
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-slate-700 hover:bg-pool-50">
                            <x-heroicon-o-arrow-right-start-on-rectangle class="h-4 w-4" aria-hidden="true" /> Sign out
                        </button>
                    </form>
                </div>
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
