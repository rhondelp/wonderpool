{{--
    x-admin.icon-picker — Searchable grid of curated heroicons (App\Support\AmenityIcons) acting as a radio group.

    @prop string                $name      Field name (default: icon)
    @prop string|null           $selected  Current icon name (old() wins)
    @prop array<string, string> $icons     icon name => search keywords (required)
    @prop string                $label     Group label (default: Icon)

    Usage: <x-admin.icon-picker :icons="$icons" :selected="$amenity->icon" />
--}}
@props([
    'name' => 'icon',
    'selected' => null,
    'icons',
    'label' => 'Icon',
])

@php
    $current = (string) old($name, $selected);
    $error = $errors->first($name);
@endphp

<fieldset x-data="{ q: '' }">
    <legend class="mb-1 block text-sm font-medium text-slate-700">{{ $label }} <span class="text-rose-600" aria-hidden="true">*</span></legend>

    <label for="{{ $name }}-search" class="sr-only">Search icons</label>
    <input type="search" id="{{ $name }}-search" x-model="q" placeholder="Search icons (e.g. pool, music, parking)" class="mb-3 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500">

    <div class="grid max-h-64 grid-cols-4 gap-2 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-6 lg:grid-cols-8" @if ($error) aria-describedby="{{ $name }}-error" @endif>
        @foreach ($icons as $icon => $keywords)
            <label
                x-show="q === '' || @js(mb_strtolower($icon.' '.$keywords)).includes(q.toLowerCase())"
                title="{{ $keywords }}"
                class="flex cursor-pointer flex-col items-center gap-1 rounded-lg p-2 text-center text-xs text-slate-600 ring-1 ring-transparent hover:bg-pool-50 has-[:checked]:bg-pool-50 has-[:checked]:text-pool-800 has-[:checked]:ring-pool-600 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-pool-500"
            >
                <input type="radio" name="{{ $name }}" value="{{ $icon }}" class="sr-only" @checked($current === $icon)>
                <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" aria-hidden="true" />
                <span class="w-full truncate">{{ $icon }}</span>
            </label>
        @endforeach
    </div>

    @if ($error)
        <p id="{{ $name }}-error" class="mt-1 text-sm text-rose-700">{{ $error }}</p>
    @endif
</fieldset>
