{{--
    Shared <head> contents for all layouts: meta/SEO tags, Vite assets, and the "styles" stack.
    Pages may set @section('title'), @section('meta_description') and @section('og_image').
    $site (resort name, SEO defaults) is shared by the view composer in AppServiceProvider (M5).
--}}
@php
    $siteName = $site['name'] ?? config('app.name');
    $description = trim($__env->yieldContent('meta_description')) ?: ($site['meta_description'] ?? 'Check availability and book online.');
    $ogImage = trim($__env->yieldContent('og_image')) ?: ($site['og_image_url'] ?? null);
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle !== '' ? $pageTitle.' · '.$siteName : $siteName;
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="{{ $description }}">
<meta name="theme-color" content="#0e7490">{{-- pool-700; meta tags cannot read CSS variables --}}
<link rel="canonical" href="{{ url()->current() }}">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="en_PH">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
@endif
<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">

<title>{{ $fullTitle }}</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('styles')
