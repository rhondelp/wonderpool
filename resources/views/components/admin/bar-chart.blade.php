{{--
    x-admin.bar-chart — Single-series vertical bar chart, server-rendered SVG (no JS, no inline styles).
    Bars: pool-600, 4px rounded tops on the baseline; recessive slate grid; native hover tooltip per bar
    (SVG <title>); a "Show data" table for screen readers / exact values. Scrolls sideways on phones.

    @prop string        $title   Chart name, shown as the caption (required)
    @prop array         $items   list of ['label' => string, 'value' => int, 'display' => string] (required)
    @prop Closure|null  $axis    fn (int $value): string for y-axis ticks (default: number_format)
    @prop string|null   $unit    Column header for the value in the data table (default: "Value")
    @prop string|null   $summary One-line takeaway under the title (optional)

    Usage: <x-admin.bar-chart title="Revenue" :items="$rows" :axis="fn ($v) => Money::compact($v)" />
--}}
@props([
    'title',
    'items',
    'axis' => null,
    'unit' => 'Value',
    'summary' => null,
])

@php
    $id = 'chart-'.\Illuminate\Support\Str::random(6);
    $width = 640;
    $height = 220;
    $left = 56;
    $right = 8;
    $top = 12;
    $bottom = 28;
    $plotW = $width - $left - $right;
    $plotH = $height - $top - $bottom;
    $count = max(count($items), 1);

    $max = max(array_merge([0], array_column($items, 'value')));
    if ($max <= 0) {
        $niceMax = 1;
    } else {
        $magnitude = 10 ** floor(log10($max));
        $niceMax = $magnitude * 10;
        foreach ([1, 2, 5, 10] as $step) {
            if ($step * $magnitude >= $max) {
                $niceMax = $step * $magnitude;
                break;
            }
        }
        // Small counts: an even top keeps the middle tick a whole number.
        if ($max < 10) {
            $niceMax = $max % 2 === 0 ? $max : $max + 1;
        }
    }
    $format = $axis ?? fn ($v) => number_format($v);
    $ticks = $max <= 0 ? [0] : [0, $niceMax / 2, $niceMax];

    $slot = $plotW / $count;
    $barW = min($slot * 0.6, 40);
    $labelEvery = (int) ceil($count / 12);

    $bar = function (int $i, int $value) use ($left, $slot, $barW, $top, $plotH, $niceMax): string {
        $h = $niceMax > 0 ? max(0, $value) / $niceMax * $plotH : 0;
        $x = $left + $i * $slot + ($slot - $barW) / 2;
        $base = $top + $plotH;
        if ($h <= 0) {
            return '';
        }
        $r = min(4, $h, $barW / 2);
        $y = $base - $h;

        return sprintf(
            'M%.2f %.2f V%.2f Q%.2f %.2f %.2f %.2f H%.2f Q%.2f %.2f %.2f %.2f V%.2f Z',
            $x, $base, $y + $r, $x, $y, $x + $r, $y, $x + $barW - $r, $x + $barW, $y, $x + $barW, $y + $r, $base
        );
    };
@endphp

<figure {{ $attributes->merge(['class' => 'rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200']) }}>
    <figcaption>
        <p id="{{ $id }}-title" class="text-sm font-semibold text-slate-900">{{ $title }}</p>
        @if ($summary)
            <p class="mt-0.5 text-sm text-slate-500">{{ $summary }}</p>
        @endif
    </figcaption>

    <div class="mt-4 overflow-x-auto">
        <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-auto w-full min-w-[32rem]" role="img" aria-labelledby="{{ $id }}-title">
            @foreach ($ticks as $tick)
                @php $y = $top + $plotH - ($niceMax > 0 ? $tick / $niceMax * $plotH : 0); @endphp
                <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $y }}" y2="{{ $y }}" @class(['stroke-slate-300' => $tick == 0, 'stroke-slate-200' => $tick != 0]) stroke-width="1" />
                <text x="{{ $left - 8 }}" y="{{ $y + 4 }}" text-anchor="end" class="fill-slate-500 text-[11px]">{{ $format((int) $tick) }}</text>
            @endforeach

            @foreach ($items as $i => $item)
                <g class="group">
                    <title>{{ $item['label'] }}: {{ $item['display'] }}</title>
                    <rect x="{{ $left + $i * $slot }}" y="{{ $top }}" width="{{ $slot }}" height="{{ $plotH }}" class="fill-transparent group-hover:fill-pool-50" />
                    @if ($path = $bar($i, (int) $item['value']))
                        <path d="{{ $path }}" class="fill-pool-600 group-hover:fill-pool-800" />
                    @endif
                    @if ($i % $labelEvery === 0)
                        <text x="{{ $left + $i * $slot + $slot / 2 }}" y="{{ $height - 8 }}" text-anchor="middle" class="fill-slate-500 text-[11px]">{{ $item['label'] }}</text>
                    @endif
                </g>
            @endforeach
        </svg>
    </div>

    <details class="mt-3 text-sm">
        <summary class="cursor-pointer text-pool-800 hover:underline">Show data</summary>
        <div class="mt-2 max-h-64 overflow-y-auto">
            <table class="min-w-full text-left">
                <caption class="sr-only">{{ $title }}</caption>
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                    <tr><th scope="col" class="py-1 pr-4">Period</th><th scope="col" class="py-1 text-right">{{ $unit }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($items as $item)
                        <tr><td class="py-1 pr-4">{{ $item['label'] }}</td><td class="py-1 text-right tabular-nums">{{ $item['display'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
