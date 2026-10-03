{{--
    Owner reports: range presets + filters, headline tiles, revenue / stays charts, per-package table,
    CSV and PDF exports. Data: Admin\ReportController@index (ReportService, D-034).
--}}
@extends('layouts.admin')

@section('title', 'Reports')

@use('App\Enums\BookingStatus')
@use('App\Support\Money')

@php
    $query = array_filter(['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'package' => $packageId]);
    $today = now()->startOfDay();
    $presets = [
        'This month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        'Last month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
        'Last 30 days' => [$today->copy()->subDays(29), $today->copy()],
        'This year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
        'Last year' => [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear()],
    ];
    $period = $granularity === 'day' ? 'day' : 'month';
@endphp

@section('content')
    <x-admin.page-header title="Reports" description="{{ $from->format('M j, Y') }} – {{ $to->format('M j, Y') }}">
        <x-slot:actions>
            <x-ui.button :href="route('admin.reports.csv', $query)" variant="secondary" icon="table-cells">Bookings CSV</x-ui.button>
            <x-ui.button :href="route('admin.reports.pdf', $query)" variant="secondary" icon="document-arrow-down">Summary PDF</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <nav class="mb-4 flex gap-2 overflow-x-auto" aria-label="Date range presets">
        @foreach ($presets as $label => [$presetFrom, $presetTo])
            @php $isCurrent = $presetFrom->isSameDay($from) && $presetTo->isSameDay($to); @endphp
            <a href="{{ route('admin.reports.index', array_filter(['from' => $presetFrom->format('Y-m-d'), 'to' => $presetTo->format('Y-m-d'), 'package' => $packageId])) }}"
               @class(['shrink-0 rounded-full px-4 py-1.5 text-sm font-medium ring-1', 'bg-pool-700 text-white ring-pool-700' => $isCurrent, 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50' => ! $isCurrent])
               @if ($isCurrent) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <form method="GET" class="mb-6 grid gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.input name="from" type="date" label="From" :value="$from->format('Y-m-d')" />
        <x-ui.input name="to" type="date" label="To" :value="$to->format('Y-m-d')" />
        <x-ui.select name="package" label="Package" :options="$packages->pluck('name', 'id')->all()" :selected="$packageId" placeholder="All packages" />
        <div class="flex items-end gap-2">
            <x-ui.button type="submit" variant="secondary" icon="funnel">Apply</x-ui.button>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card label="Revenue received" :value="Money::format($summary['revenue_cents'])" icon="banknotes" color="garden" :hint="$summary['payments'].' verified '.\Illuminate\Support\Str::plural('payment', $summary['payments'])" />
        <x-admin.stat-card label="Booked value" :value="Money::format($summary['booked_value_cents'])" icon="receipt-percent" color="pool" :hint="'Avg '.Money::format($summary['average_cents']).' per stay'" />
        <x-admin.stat-card label="Confirmed stays" :value="$summary['confirmed']" icon="check-circle" color="pool" :hint="$summary['bookings'].' bookings in total'" />
        <x-admin.stat-card label="Occupancy" :value="$summary['occupancy_percent'].'%'" icon="calendar-days" color="amber" :hint="$summary['occupied_days'].' of '.$summary['days'].' days with a confirmed stay'" />
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        <x-admin.bar-chart
            title="Revenue received per {{ $period }}"
            summary="Verified payments, by the date they were confirmed"
            unit="Revenue"
            :items="array_map(fn ($r) => ['label' => $r['label'], 'value' => $r['revenue_cents'], 'display' => Money::format($r['revenue_cents'])], $series)"
            :axis="fn ($v) => Money::compact($v)"
        />
        <x-admin.bar-chart
            title="Confirmed stays per {{ $period }}"
            summary="Approved and completed bookings, by stay date"
            unit="Stays"
            :items="array_map(fn ($r) => ['label' => $r['label'], 'value' => $r['bookings'], 'display' => (string) $r['bookings']], $series)"
        />
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-3">
        <x-ui.card title="Bookings by status" :padded="false">
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach (BookingStatus::cases() as $status)
                    <li class="flex items-center justify-between px-5 py-3">
                        <x-ui.badge :status="$status" />
                        <span class="font-semibold tabular-nums">{{ $summary['by_status'][$status->value] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        @if ($packageId === null)
            <x-ui.card title="By package" :padded="false" class="xl:col-span-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">Package</th>
                                <th scope="col" class="px-5 py-3 text-right">Stays</th>
                                <th scope="col" class="px-5 py-3 text-right">Booked value</th>
                                <th scope="col" class="px-5 py-3 text-right">Revenue received</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($byPackage as $row)
                                <tr>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('admin.reports.index', array_filter(['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'package' => $row['package']->id])) }}" class="text-pool-800 hover:underline">{{ $row['package']->name }}</a>
                                        @unless ($row['package']->is_active)<span class="ml-1 text-xs text-slate-500">(inactive)</span>@endunless
                                    </td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $row['bookings'] }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ Money::format($row['booked_value_cents']) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ Money::format($row['revenue_cents']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">No packages yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif
    </div>

    <p class="mt-6 text-xs text-slate-500">
        Revenue counts verified payments on the day they were confirmed. Booked value, stays and occupancy count approved and completed bookings by their stay date.
        The CSV lists every booking whose stay starts in the range.
    </p>
@endsection
