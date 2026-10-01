{{--
    Admin bookings list: status tabs with counts, filters, search, sortable columns.
    Data: Admin\BookingController@index (BookingAdminService::listing/statusCounts, PaymentService::summary).
--}}
@extends('layouts.admin')

@section('title', 'Bookings')

@php
    $currentStatus = request('status');
    $sortLink = function (string $column) {
        $isCurrent = request('sort', 'starts_at') === $column;
        $dir = $isCurrent && request('dir', $column === 'starts_at' ? 'asc' : 'desc') === 'asc' ? 'desc' : 'asc';

        return request()->fullUrlWithQuery(['sort' => $column, 'dir' => $dir, 'page' => null]);
    };
    $sortIcon = fn (string $column) => request('sort', 'starts_at') === $column ? (request('dir', $column === 'starts_at' ? 'asc' : 'desc') === 'asc' ? '▲' : '▼') : '';
    $columns = ['reference_code' => 'Reference', 'guest_name' => 'Guest', 'starts_at' => 'Stay', 'total_amount_cents' => 'Total', 'created_at' => 'Booked'];
@endphp

@section('content')
    <x-admin.page-header title="Bookings" description="Review requests, confirm payments and manage stays.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.bookings.create')" icon="plus">Walk-in booking</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Status tabs --}}
    <nav class="mb-4 flex gap-2 overflow-x-auto" aria-label="Booking status">
        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" @class(['shrink-0 rounded-full px-4 py-1.5 text-sm font-medium ring-1', 'bg-pool-700 text-white ring-pool-700' => ! $currentStatus, 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50' => $currentStatus]) @if (! $currentStatus) aria-current="page" @endif>
            All <span class="ml-1 opacity-80">{{ array_sum($counts) }}</span>
        </a>
        @foreach ($statuses as $status)
            <a href="{{ request()->fullUrlWithQuery(['status' => $status->value, 'page' => null]) }}" @class(['shrink-0 rounded-full px-4 py-1.5 text-sm font-medium ring-1', 'bg-pool-700 text-white ring-pool-700' => $currentStatus === $status->value, 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50' => $currentStatus !== $status->value]) @if ($currentStatus === $status->value) aria-current="page" @endif>
                {{ $status->label() }} <span class="ml-1 opacity-80">{{ $counts[$status->value] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Filters --}}
    <form method="GET" role="search" class="mb-4 grid gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-6">
        @if ($currentStatus)<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
        <div class="lg:col-span-2">
            <label for="q" class="mb-1 block text-xs font-medium text-slate-600">Search</label>
            <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Reference, name, phone or email" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500">
        </div>
        <div>
            <label for="package" class="mb-1 block text-xs font-medium text-slate-600">Package</label>
            <select id="package" name="package" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500">
                <option value="">All packages</option>
                @foreach ($packages as $package)
                    <option value="{{ $package->id }}" @selected((string) request('package') === (string) $package->id)>{{ $package->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="payment" class="mb-1 block text-xs font-medium text-slate-600">Payment</label>
            <select id="payment" name="payment" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500">
                <option value="">Any</option>
                @foreach ($paymentStates as $state)
                    <option value="{{ $state->value }}" @selected(request('payment') === $state->value)>{{ $state->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="from" class="mb-1 block text-xs font-medium text-slate-600">Stay from</label>
            <input type="date" id="from" name="from" value="{{ request('from') }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500">
        </div>
        <div>
            <label for="to" class="mb-1 block text-xs font-medium text-slate-600">Stay to</label>
            <input type="date" id="to" name="to" value="{{ request('to') }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500">
        </div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-6">
            <x-ui.button type="submit" variant="secondary" icon="funnel">Filter</x-ui.button>
            @if (request()->hasAny(['q', 'package', 'payment', 'from', 'to', 'sort']))
                <x-ui.button :href="route('admin.bookings.index', array_filter(['status' => $currentStatus]))" variant="ghost">Clear</x-ui.button>
            @endif
        </div>
    </form>

    @if ($bookings->isEmpty())
        <x-ui.empty-state title="No bookings found" :description="request()->hasAny(['q', 'status', 'package', 'payment', 'from', 'to']) ? 'Try other filters.' : 'New booking requests from the website appear here.'" icon="calendar-days" />
    @else
        <x-ui.card :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            @foreach ($columns as $column => $label)
                                <th scope="col" class="px-4 py-3" @if (request('sort', 'starts_at') === $column) aria-sort="{{ $sortIcon($column) === '▲' ? 'ascending' : 'descending' }}" @endif>
                                    <a href="{{ $sortLink($column) }}" class="inline-flex items-center gap-1 hover:text-slate-800">{{ $label }} <span aria-hidden="true">{{ $sortIcon($column) }}</span></a>
                                </th>
                            @endforeach
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($bookings as $booking)
                            @php $summary = $summaries[$booking->id]; @endphp
                            <tr class="hover:bg-pool-50/50">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="font-mono font-semibold text-pool-800 hover:underline">{{ $booking->reference_code }}</a>
                                    @if ($booking->source === \App\Enums\BookingSource::Admin)<span class="block text-xs text-slate-500">Walk-in</span>@endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ $booking->guest_name }}</p>
                                    <p class="text-xs text-slate-500">{{ \App\Support\PhoneNumber::display($booking->guest_phone) }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="text-slate-900">{{ $booking->starts_at->format('D, M j, Y') }}</p>
                                    <p class="text-xs text-slate-500">{{ $booking->package->name }} · {{ $booking->guest_count }} pax</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ $booking->formatted_total }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $booking->created_at?->format('M j, g:i A') }}</td>
                                <td class="px-4 py-3"><x-ui.badge :status="$booking->status" /></td>
                                <td class="px-4 py-3"><x-ui.badge :color="$summary['state']->color()">{{ $summary['state']->label() }}</x-ui.badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($bookings->hasPages())
                <x-slot:footer>{{ $bookings->links() }}</x-slot:footer>
            @endif
        </x-ui.card>
    @endif
@endsection
