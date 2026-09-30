{{--
    Shared <head> contents for all layouts: meta tags, Vite assets, and the "styles" stack.
    Pages may set @section('title') and @section('meta_description').
--}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="@yield('meta_description', 'Wonderpool Garden Resort — pools, gardens, rooms and cottages. Check availability and book online.')">
<meta name="theme-color" content="#0e7490">{{-- pool-700; meta tags cannot read CSS variables --}}

<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="@hasSection('title')@yield('title') · @endif{{ config('app.name') }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">

<title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('styles')
