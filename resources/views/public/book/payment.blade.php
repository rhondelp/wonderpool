{{-- Booking step 3b: pay and upload proof. Data: Public\BookingController@payment. --}}
@extends('layouts.public')

@section('title', 'Send your payment')

@section('content')
    <x-public.page-hero title="Almost done!" subtitle="Your date is held for you. Send your downpayment and upload the receipt to complete your booking request." />

    <div class="mx-auto grid max-w-5xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="space-y-6">
            <h2 class="text-lg font-semibold text-pool-900">Your booking</h2>
            @include('public.book._summary')
            <p class="text-sm text-slate-600">Unpaid requests are released automatically if no payment proof arrives in time.</p>
        </div>

        <div class="space-y-6">
            @if ($paymentInstructions)
                <div class="rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200">
                    <h2 class="font-semibold text-amber-900">How to pay {{ $booking->formatted_downpayment }}</h2>
                    <x-ui.prose :text="$paymentInstructions" class="mt-2 text-sm" />
                    <p class="mt-2 text-sm text-amber-900">Use <strong class="font-mono">{{ $booking->reference_code }}</strong> as the message/notes of your transfer.</p>
                </div>
            @endif

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 font-semibold text-pool-900">Upload your payment proof</h2>
                @include('public.book._proof-form', ['submitLabel' => 'Send payment proof'])
            </div>

            <p class="text-sm text-slate-600">Paying later? Your reference is <strong class="font-mono">{{ $booking->reference_code }}</strong>. Use <a href="{{ route('track') }}" class="font-medium text-pool-700 underline">Track booking</a> with your mobile number to upload the proof.</p>
        </div>
    </div>
@endsection
