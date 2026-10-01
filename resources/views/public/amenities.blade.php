{{-- Amenities page. Data: PageController@amenities. --}}
@extends('layouts.public')

@section('title', 'Amenities')

@section('content')
    <x-public.page-hero title="Amenities" subtitle="Pools, gardens and spaces for your celebration. The whole resort is yours during your booking." />

    <x-ui.section>
        @if ($amenities->isEmpty())
            <x-ui.empty-state title="Amenities coming soon" description="Please check back later or contact us for details." icon="sparkles" />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($amenities as $amenity)
                    <x-public.amenity-card :amenity="$amenity" />
                @endforeach
            </div>
        @endif
    </x-ui.section>
@endsection
