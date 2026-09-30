{{-- Temporary landing page (M0). Replaced by the real public home page in M5. --}}
@extends('layouts.public')

@section('content')
    <section class="bg-gradient-to-br from-pool-700 via-pool-600 to-garden-600 text-white">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold sm:text-5xl">{{ config('app.name') }}</h1>
            <p class="mt-4 max-w-xl text-lg text-pool-50">Cool pools, green gardens. Online booking is coming soon.</p>
        </div>
    </section>
@endsection
