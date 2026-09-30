{{--
    x-admin.page-header — Title bar at the top of every admin page.

    @prop string      $title        Page title (required)
    @prop string|null $description  Subtitle (optional)
    @slot actions                   Right-side buttons (optional)

    Usage: <x-admin.page-header title="Rooms" description="Manage rooms and cottages.">
               <x-slot:actions><x-ui.button icon="plus">Add room</x-ui.button></x-slot:actions>
           </x-admin.page-header>
--}}
@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold text-slate-900">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
