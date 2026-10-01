{{-- Track booking form: reference + mobile number (both required). Data: Public\TrackBookingController@create. --}}
@extends('layouts.public')

@section('title', 'Track your booking')

@section('content')
    <x-public.page-hero title="Track your booking" subtitle="Enter the reference code you received and the mobile number you used when booking." />

    <div class="mx-auto max-w-md px-4 py-10 sm:px-6">
        <form method="POST" action="{{ route('track.lookup') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @csrf
            <x-ui.input name="reference" label="Reference code" placeholder="WP-2610-A7K3" autocomplete="off" autocapitalize="characters" class="font-mono uppercase" required />
            <x-ui.input name="phone" type="tel" label="Mobile number" placeholder="0917 123 4567" autocomplete="tel" inputmode="tel" required />
            <x-ui.button type="submit" class="w-full" icon="magnifying-glass">Find my booking</x-ui.button>
        </form>
    </div>
@endsection
