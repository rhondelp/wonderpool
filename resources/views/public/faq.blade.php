{{-- FAQ page (native <details> accordion, works without JS). Data: PageController@faq. --}}
@extends('layouts.public')

@section('title', 'FAQ')

@section('content')
    <x-public.page-hero title="Frequently asked questions" />

    <x-ui.section>
        @if ($faqs->isEmpty())
            <x-ui.empty-state title="No questions yet" description="Contact us and we will be happy to help." icon="question-mark-circle">
                <x-ui.button :href="route('contact')" class="mt-4">Contact us</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="mx-auto max-w-3xl space-y-3">
                @foreach ($faqs as $faq)
                    <details class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 open:ring-pool-300">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-pool-900">
                            {{ $faq->question }}
                            <x-heroicon-o-chevron-down class="h-5 w-5 shrink-0 text-pool-600 transition group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <div class="mt-3 whitespace-pre-line text-slate-700">{{ $faq->answer }}</div>
                    </details>
                @endforeach
            </div>
        @endif
    </x-ui.section>
@endsection
