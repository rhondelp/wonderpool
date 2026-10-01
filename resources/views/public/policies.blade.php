{{-- House rules & policies: settings content.house_rules and content.cancellation_policy. --}}
@extends('layouts.public')

@section('title', 'House rules & policies')

@section('content')
    <x-public.page-hero title="House rules & policies" />

    <x-ui.section>
        <div class="mx-auto max-w-3xl space-y-10">
            @if (blank($houseRules) && blank($cancellationPolicy))
                <x-ui.empty-state title="Policies coming soon" description="Please contact us if you have questions." icon="document-text" />
            @endif

            @if (filled($houseRules))
                <section aria-labelledby="house-rules">
                    <h2 id="house-rules" class="mb-4 text-xl font-semibold text-pool-900">House rules</h2>
                    <x-ui.prose :text="$houseRules" />
                </section>
            @endif

            @if (filled($cancellationPolicy))
                <section aria-labelledby="cancellation">
                    <h2 id="cancellation" class="mb-4 text-xl font-semibold text-pool-900">Cancellation &amp; rescheduling</h2>
                    <x-ui.prose :text="$cancellationPolicy" />
                </section>
            @endif
        </div>
    </x-ui.section>
@endsection
