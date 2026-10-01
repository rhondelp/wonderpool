{{-- Contact page: settings contact.* and social.* (shared as $site). --}}
@extends('layouts.public')

@section('title', 'Contact')

@section('content')
    <x-public.page-hero title="Contact us" subtitle="Questions about packages, dates or payment? Reach us here." />

    <x-ui.section>
        <div class="grid gap-8 lg:grid-cols-2">
            <ul class="space-y-4" role="list">
                @if ($site['phone'])
                    <li class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <x-heroicon-o-phone class="h-6 w-6 text-pool-700" aria-hidden="true" />
                        <div><p class="text-sm text-slate-500">Phone</p><a href="tel:{{ preg_replace('/[^\d+]/', '', $site['phone']) }}" class="font-medium text-pool-800 hover:underline">{{ $site['phone'] }}</a></div>
                    </li>
                @endif
                @if ($site['email'])
                    <li class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <x-heroicon-o-envelope class="h-6 w-6 text-pool-700" aria-hidden="true" />
                        <div><p class="text-sm text-slate-500">Email</p><a href="mailto:{{ $site['email'] }}" class="font-medium text-pool-800 hover:underline">{{ $site['email'] }}</a></div>
                    </li>
                @endif
                @if ($site['address'])
                    <li class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <x-heroicon-o-map-pin class="h-6 w-6 text-pool-700" aria-hidden="true" />
                        <div><p class="text-sm text-slate-500">Address</p><p class="font-medium text-slate-800">{{ $site['address'] }}</p></div>
                    </li>
                @endif
                @if ($site['facebook_url'] || $site['instagram_url'])
                    <li class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <x-heroicon-o-chat-bubble-left-right class="h-6 w-6 text-pool-700" aria-hidden="true" />
                        <div>
                            <p class="text-sm text-slate-500">Message us</p>
                            <p class="flex gap-4 font-medium">
                                @if ($site['facebook_url'])<a href="{{ $site['facebook_url'] }}" class="text-pool-800 hover:underline" rel="noopener" target="_blank">Facebook</a>@endif
                                @if ($site['instagram_url'])<a href="{{ $site['instagram_url'] }}" class="text-pool-800 hover:underline" rel="noopener" target="_blank">Instagram</a>@endif
                            </p>
                        </div>
                    </li>
                @endif
            </ul>

            @if ($site['map_embed_url'])
                <div class="overflow-hidden rounded-2xl shadow-sm ring-1 ring-slate-200">
                    <iframe src="{{ $site['map_embed_url'] }}" title="Map to {{ $site['name'] }}" class="h-full min-h-80 w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            @endif
        </div>
    </x-ui.section>
@endsection
