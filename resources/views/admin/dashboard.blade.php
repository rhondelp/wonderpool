@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <x-admin.page-header title="Dashboard" description="{{ now()->format('l, F j, Y') }}" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card label="Pending approval" :value="$summary['pending']" icon="clock" color="amber" />
        <x-admin.stat-card label="Arrivals today" :value="$summary['arrivals_today']" icon="arrow-right-end-on-rectangle" color="garden" />
        <x-admin.stat-card label="Next 7 days" :value="$summary['upcoming_week']" icon="calendar-days" color="pool" />
        @can('view-financials')
            <x-admin.stat-card label="Revenue this month" :value="\App\Support\Money::format($summary['revenue_month_cents'])" icon="banknotes" color="garden" hint="Verified payments" />
        @endcan
    </div>

    <x-ui.card title="Upcoming bookings" class="mt-6" :padded="$upcoming->isEmpty()">
        @if ($upcoming->isEmpty())
            <x-ui.empty-state title="No upcoming bookings" description="Pending and approved bookings will appear here." icon="calendar" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Reference</th>
                            <th scope="col" class="px-5 py-3">Guest</th>
                            <th scope="col" class="px-5 py-3">Package</th>
                            <th scope="col" class="px-5 py-3">Starts</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($upcoming as $booking)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{{ $booking->reference_code }}</td>
                                <td class="px-5 py-3">{{ $booking->guest_name }}</td>
                                <td class="px-5 py-3">{{ $booking->package->name }}</td>
                                <td class="whitespace-nowrap px-5 py-3">{{ $booking->starts_at->format('M j, Y g:i A') }}</td>
                                <td class="px-5 py-3"><x-ui.badge :status="$booking->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <p class="mt-6 text-sm text-slate-500">Occupancy and revenue charts arrive with Reports.</p>
@endsection
