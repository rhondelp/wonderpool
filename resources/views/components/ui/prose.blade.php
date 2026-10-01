{{--
    x-ui.prose — Renders owner-entered settings text as safe Markdown-lite (D-027):
    raw HTML is stripped, unsafe links are dropped; supports **bold**, lists (- item), links, paragraphs.

    @prop string|null $text  Setting value (renders nothing when blank)

    Usage: <x-ui.prose :text="$houseRules" />
--}}
@props([
    'text' => null,
])

@if (filled($text))
    <div {{ $attributes->merge(['class' => 'space-y-3 text-slate-700 leading-relaxed [&_a]:font-medium [&_a]:text-pool-700 [&_a]:underline [&_li]:ml-5 [&_ol]:list-decimal [&_strong]:text-slate-900 [&_ul]:list-disc [&_ul]:space-y-1']) }}>
        {!! \Illuminate\Support\Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
@endif
