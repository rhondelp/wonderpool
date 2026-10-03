{{--
    Admin sidebar contents, shared by the desktop sidebar and mobile drawer in layouts/admin.

    @param array<int, array{label: string, icon: string, route: string, active: string}> $nav
--}}
<div class="flex h-16 shrink-0 items-center gap-2 px-6 text-lg font-semibold text-white">
    <x-heroicon-o-sun class="h-7 w-7 text-garden-400" aria-hidden="true" />
    <span>Wonderpool</span>
</div>

<nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Admin">
    <ul class="space-y-1">
        @foreach ($nav as $item)
            @php $active = request()->routeIs($item['active']); @endphp
            <li>
                <a
                    href="{{ route($item['route']) }}"
                    @if ($active) aria-current="page" @endif
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        'bg-pool-800 text-white' => $active,
                        'text-pool-100 hover:bg-pool-900 hover:text-white' => ! $active,
                    ])
                >
                    <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="h-5 w-5 shrink-0" aria-hidden="true" />
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>

<p class="border-t border-pool-900 px-6 py-4 text-xs text-pool-300">
    Developed by <a href="mailto:{{ config('wonderpool.developer.email') }}" class="font-medium text-pool-100 hover:text-white hover:underline">{{ config('wonderpool.developer.name') }}</a>
</p>
