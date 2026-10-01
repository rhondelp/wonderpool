{{--
    Minimal centered layout for admin sign-in and the forced password change.

    Sections: title, heading, content
    Stacks:   styles (in <head>), scripts (end of <body>)
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="flex min-h-full flex-col items-center justify-center bg-gradient-to-br from-pool-50 via-white to-garden-50 px-4 py-12 font-sans text-slate-800 antialiased">
    <main class="w-full max-w-md">
        <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-2 text-xl font-semibold text-pool-800">
            <x-heroicon-o-sun class="h-8 w-8 text-garden-600" aria-hidden="true" />
            <span>Wonderpool <span class="text-garden-700">Garden</span></span>
        </a>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <h1 class="text-xl font-semibold text-slate-900">@yield('heading')</h1>
            <x-ui.flash class="mt-4" />
            <div class="mt-6">
                @yield('content')
            </div>
        </div>
    </main>

    @stack('scripts')
</body>
</html>
