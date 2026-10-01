{{-- Booking success page. Data: Public\BookingController@done. --}}
@extends('layouts.public')

@section('title', 'Booking received')

@section('content')
    <x-public.page-hero title="Thank you! We received your booking request." />

    <div class="mx-auto max-w-3xl space-y-8 px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-gradient-to-br from-pool-50 to-garden-50 p-6 text-center ring-1 ring-pool-200 sm:p-10" x-data="{ copied: false }">
            <p class="text-sm font-medium uppercase tracking-widest text-slate-600">Your reference code</p>
            <p class="mt-2 select-all font-mono text-4xl font-semibold tracking-widest text-pool-900 sm:text-5xl" id="reference-code">{{ $booking->reference_code }}</p>
            <button type="button" x-cloak
                    x-on:click="navigator.clipboard.writeText(@js($booking->reference_code)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-pool-800 ring-1 ring-pool-200 hover:bg-pool-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-pool-500">
                <x-heroicon-o-clipboard-document class="h-5 w-5" aria-hidden="true" />
                <span x-text="copied ? 'Copied!' : 'Copy code'">Copy code</span>
            </button>
            <p class="sr-only" aria-live="polite" x-text="copied ? 'Reference code copied' : ''"></p>
            <p class="mt-4 text-sm text-slate-600">Save this code (take a screenshot). You need it with your mobile number to track your booking.</p>
        </div>

        @if ($booking->payments->whereNotNull('proof_path')->isNotEmpty())
            <x-ui.alert type="success" title="Payment proof received">We will check it and update your booking status.</x-ui.alert>
        @else
            <x-ui.alert type="warning" title="Payment proof still needed">Upload it from the tracking page to keep your date.</x-ui.alert>
        @endif

        @if ($nextSteps)
            <section aria-labelledby="next-steps">
                <h2 id="next-steps" class="mb-3 text-lg font-semibold text-pool-900">What happens next</h2>
                <x-ui.prose :text="$nextSteps" />
            </section>
        @endif

        <div>
            <h2 class="mb-3 text-lg font-semibold text-pool-900">Booking summary</h2>
            @include('public.book._summary')
        </div>

        <div class="flex flex-wrap gap-3">
            <x-ui.button :href="route('track.show', $booking)" icon="magnifying-glass">Track this booking</x-ui.button>
            <x-ui.button :href="route('home')" variant="secondary">Back to home</x-ui.button>
        </div>
    </div>
@endsection
