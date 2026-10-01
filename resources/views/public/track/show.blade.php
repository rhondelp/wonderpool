{{-- Booking status page with timeline; proof upload while pending. Data: Public\TrackBookingController@show. --}}
@extends('layouts.public')

@section('title', 'Booking '.$booking->reference_code)

@php
    $toneDots = ['pool' => 'bg-pool-600', 'garden' => 'bg-garden-600', 'amber' => 'bg-amber-500', 'rose' => 'bg-rose-600', 'slate' => 'bg-slate-400'];
    $proof = $booking->payments->whereNotNull('proof_path')->sortByDesc('id')->first();
@endphp

@section('content')
    <x-public.page-hero :title="'Booking '.$booking->reference_code" />

    <div class="mx-auto grid max-w-5xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="space-y-6">
            @include('public.book._summary')

            @if ($booking->rejection_reason && $booking->status === \App\Enums\BookingStatus::Rejected)
                <x-ui.alert type="error" title="Reason">{{ $booking->rejection_reason }}</x-ui.alert>
            @endif
        </div>

        <div class="space-y-6">
            <section aria-labelledby="timeline-title" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 id="timeline-title" class="mb-4 font-semibold text-pool-900">Timeline</h2>
                @if ($timeline === [])
                    <p class="text-sm text-slate-600">No updates yet.</p>
                @else
                    <ol class="relative space-y-5 border-l-2 border-pool-100 pl-6">
                        @foreach ($timeline as $item)
                            <li class="relative">
                                <span class="absolute -left-[31px] top-1 h-3.5 w-3.5 rounded-full ring-4 ring-white {{ $toneDots[$item['tone']] ?? 'bg-slate-400' }}" aria-hidden="true"></span>
                                <p class="font-medium text-slate-900">{{ $item['label'] }}</p>
                                <p class="text-xs text-slate-500"><time datetime="{{ $item['at']->toIso8601String() }}">{{ $item['at']->format('M j, Y g:i A') }}</time></p>
                                @if ($item['note'])
                                    <p class="mt-1 text-sm text-slate-700">{{ $item['note'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            @if ($canUpload)
                <section aria-labelledby="proof-title" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 id="proof-title" class="mb-2 font-semibold text-pool-900">{{ $proof ? 'Replace payment proof' : 'Upload payment proof' }}</h2>
                    @if ($proof)
                        <p class="mb-4 text-sm text-slate-600">We received your proof on {{ $proof->updated_at?->format('M j, Y g:i A') }}. Upload again only if you sent the wrong file.</p>
                    @elseif ($paymentInstructions)
                        <x-ui.prose :text="$paymentInstructions" class="mb-4 text-sm" />
                    @endif
                    @include('public.book._proof-form', ['return' => 'track', 'submitLabel' => $proof ? 'Replace proof' : 'Send payment proof'])
                </section>
            @endif
        </div>
    </div>
@endsection
