{{-- Packages & rates page. Data: PageController@packages. --}}
@extends('layouts.public')

@section('title', 'Packages & rates')

@section('content')
    <x-public.page-hero title="Packages & rates" subtitle="Exclusive use of the resort. Prices shown are base rates; weekend, holiday and seasonal rates may apply. You see the exact price before you book." />

    <x-ui.section>
        @if ($packages->isEmpty())
            <x-ui.empty-state title="Packages coming soon" description="Please contact us for rates." icon="cube" />
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($packages as $package)
                    <x-public.package-card :package="$package" />
                @endforeach
            </div>
        @endif
    </x-ui.section>

    <x-ui.section title="Add-ons" intro="Optional extras you can add while booking." :when="$addOns->isNotEmpty()" class="bg-gradient-to-b from-white to-pool-50">
        <ul class="mx-auto grid max-w-4xl gap-3 sm:grid-cols-2" role="list">
            @foreach ($addOns as $addOn)
                <li class="flex items-start justify-between gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <div>
                        <p class="font-medium text-slate-900">{{ $addOn->name }}</p>
                        @if ($addOn->description)
                            <p class="text-sm text-slate-600">{{ $addOn->description }}</p>
                        @endif
                    </div>
                    <p class="whitespace-nowrap font-semibold text-pool-800">{{ $addOn->formatted_price }}</p>
                </li>
            @endforeach
        </ul>
    </x-ui.section>
@endsection
