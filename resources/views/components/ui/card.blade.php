{{--
    x-ui.card — White surface container with optional header and footer.

    @prop string|null $title    Header title (optional)
    @prop bool        $padded   Apply body padding (default: true)
    @slot default               Card body
    @slot actions               Header right-side actions (optional)
    @slot footer                Footer content (optional)

    Usage: <x-ui.card title="Guests"><x-slot:actions>...</x-slot:actions> body </x-ui.card>
--}}
@props([
    'title' => null,
    'padded' => true,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
            @if ($title)
                <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
            @endif
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div @class(['p-5' => $padded])>
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-slate-200 bg-slate-50 px-5 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>
