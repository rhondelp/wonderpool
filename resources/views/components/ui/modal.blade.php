{{--
    x-ui.modal — Accessible Alpine dialog. Open with $dispatch('open-modal', '<name>'),
    close with $dispatch('close-modal', '<name>'), Escape, or backdrop click.

    @prop string      $name      Unique modal name used by the open/close events (required)
    @prop string|null $title     Dialog title (optional)
    @prop string      $maxWidth  sm|md|lg|xl (default: md)
    @prop bool        $show      Open on page load, e.g. after a validation error (default: false)
    @slot default                Dialog body
    @slot footer                 Footer actions (optional)

    Usage: <x-ui.button x-data @click="$dispatch('open-modal', 'confirm-cancel')">Cancel</x-ui.button>
           <x-ui.modal name="confirm-cancel" title="Cancel booking?">...</x-ui.modal>
--}}
@props([
    'name',
    'title' => null,
    'maxWidth' => 'md',
    'show' => false,
])

@php
    $widths = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-lg', 'lg' => 'sm:max-w-2xl', 'xl' => 'sm:max-w-4xl'];
@endphp

<div
    x-data="{ open: @js($show) }"
    x-on:open-modal.window="if ($event.detail === @js($name)) open = true"
    x-on:close-modal.window="if ($event.detail === @js($name)) open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="modal-{{ $name }}-title" @endif
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/50" x-on:click="open = false"></div>

    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div
            x-show="open"
            x-transition
            x-trap.noscroll="open"
            {{ $attributes->merge(['class' => 'relative w-full rounded-xl bg-white shadow-xl '.($widths[$maxWidth] ?? $widths['md'])]) }}
        >
            <div class="flex items-start justify-between gap-4 px-5 pt-5">
                @if ($title)
                    <h2 id="modal-{{ $name }}-title" class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
                @endif
                <button type="button" x-on:click="open = false" class="ml-auto rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-pool-500">
                    <span class="sr-only">Close</span>
                    <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>

            <div class="px-5 py-4 text-sm text-slate-700">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex flex-col-reverse gap-2 rounded-b-xl bg-slate-50 px-5 py-3 sm:flex-row sm:justify-end">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
