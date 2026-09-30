{{--
    x-ui.empty-state — Placeholder for lists/tables with no records.

    @prop string      $title        Main message (default: "Nothing here yet")
    @prop string|null $description  Supporting text (optional)
    @prop string      $icon         Heroicon outline name (default: inbox)
    @slot default                   Call-to-action, e.g. a button (optional)

    Usage: <x-ui.empty-state title="No bookings yet" description="New requests appear here."><x-ui.button>...</x-ui.button></x-ui.empty-state>
--}}
@props([
    'title' => 'Nothing here yet',
    'description' => null,
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 px-6 py-12 text-center']) }}>
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-pool-50">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6 text-pool-600" aria-hidden="true" />
    </div>
    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
