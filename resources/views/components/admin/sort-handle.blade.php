{{--
    x-admin.sort-handle — Drag grip plus "move up/down" buttons for one item inside x-admin.sortable.

    @prop string $label  Item name for screen readers, e.g. the FAQ question (required)

    Usage: <tr data-sortable-id="{{ $faq->id }}" draggable="true"><td><x-admin.sort-handle :label="$faq->question" /></td>…</tr>
--}}
@props([
    'label',
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-0.5']) }}>
    <span class="cursor-grab p-1 text-slate-400 active:cursor-grabbing" title="Drag to reorder" aria-hidden="true">
        <x-heroicon-m-bars-3 class="h-5 w-5" />
    </span>
    <button type="button" x-on:click="move($el, -1)" class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-pool-500">
        <span class="sr-only">Move {{ $label }} up</span>
        <x-heroicon-m-chevron-up class="h-4 w-4" aria-hidden="true" />
    </button>
    <button type="button" x-on:click="move($el, 1)" class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-pool-500">
        <span class="sr-only">Move {{ $label }} down</span>
        <x-heroicon-m-chevron-down class="h-4 w-4" aria-hidden="true" />
    </button>
</div>
