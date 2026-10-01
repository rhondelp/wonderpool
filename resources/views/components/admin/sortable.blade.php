{{--
    x-admin.sortable — Wraps a list/table/grid whose items can be reordered by drag-and-drop
    or the keyboard (Alpine `sortable`, resources/js/sortable.js). Saves via PATCH {"ids": [...]}.

    @prop string $url   Reorder endpoint, e.g. route('admin.faqs.reorder') (required)
    @prop string $axis  "y" for lists/tables (default), "x" for grids
    @slot default       Markup whose items carry data-sortable-id="{{ $id }}" draggable="true"
                        and contain an <x-admin.sort-handle /> (same parent element for all items)

    Usage: <x-admin.sortable :url="route('admin.faqs.reorder')"><table>…<tr data-sortable-id="1" draggable="true">…</x-admin.sortable>
--}}
@props([
    'url',
    'axis' => 'y',
])

<div
    x-data="sortable({ url: @js($url), axis: @js($axis) })"
    x-on:dragstart="start($event)"
    x-on:dragover="over($event)"
    x-on:drop.prevent
    x-on:dragend="end()"
    {{ $attributes }}
>
    {{ $slot }}

    <p
        role="status"
        aria-live="polite"
        x-text="message"
        x-show="message !== ''"
        x-cloak
        x-bind:class="failed ? 'text-rose-700' : 'text-slate-500'"
        class="px-5 py-2 text-xs"
    ></p>
</div>
