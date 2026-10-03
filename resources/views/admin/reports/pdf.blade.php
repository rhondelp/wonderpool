{{--
    Report summary for DomPDF (tables only; DomPDF has limited SVG support). Uses layouts/print (D-033).
    Data: ReportExportService::pdfData().
--}}
@extends('layouts.print')

@use('App\Enums\BookingStatus')
@use('App\Support\Money')

@section('title', 'Report '.$from->format('Y-m-d').' to '.$to->format('Y-m-d'))

@section('content')
    <h1>{{ config('app.name') }} — Report</h1>
    <p class="muted">
        {{ $from->format('M j, Y') }} – {{ $to->format('M j, Y') }} · {{ $package?->name ?? 'All packages' }}<br>
        Generated {{ now()->format('M j, Y g:i A') }}
    </p>

    <h2>Summary</h2>
    <table>
        <tr><td class="label-col">Revenue received</td><td class="num">{{ Money::format($summary['revenue_cents']) }}</td><td class="muted">{{ $summary['payments'] }} verified payments</td></tr>
        <tr><td>Booked value</td><td class="num">{{ Money::format($summary['booked_value_cents']) }}</td><td class="muted">avg {{ Money::format($summary['average_cents']) }} per stay</td></tr>
        <tr><td>Confirmed stays</td><td class="num">{{ $summary['confirmed'] }}</td><td class="muted">{{ $summary['bookings'] }} bookings in total</td></tr>
        <tr><td>Occupancy</td><td class="num">{{ $summary['occupancy_percent'] }}%</td><td class="muted">{{ $summary['occupied_days'] }} of {{ $summary['days'] }} days</td></tr>
    </table>

    <h2>Bookings by status</h2>
    <table>
        @foreach (BookingStatus::cases() as $status)
            <tr><td class="label-col">{{ $status->label() }}</td><td class="num">{{ $summary['by_status'][$status->value] }}</td><td></td></tr>
        @endforeach
    </table>

    @if ($packages !== [])
        <h2>By package</h2>
        <table>
            <tr><th>Package</th><th class="num">Stays</th><th class="num">Booked value</th><th class="num">Revenue received</th></tr>
            @foreach ($packages as $row)
                <tr>
                    <td>{{ $row['package']->name }}</td>
                    <td class="num">{{ $row['bookings'] }}</td>
                    <td class="num">{{ Money::format($row['booked_value_cents']) }}</td>
                    <td class="num">{{ Money::format($row['revenue_cents']) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Per {{ $granularity === 'day' ? 'day' : 'month' }}</h2>
    <table>
        <tr><th>Period</th><th class="num">Confirmed stays</th><th class="num">Revenue received</th></tr>
        @foreach ($series as $row)
            <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['bookings'] }}</td><td class="num">{{ Money::format($row['revenue_cents']) }}</td></tr>
        @endforeach
        <tr class="total"><td>Total</td><td class="num">{{ array_sum(array_column($series, 'bookings')) }}</td><td class="num">{{ Money::format(array_sum(array_column($series, 'revenue_cents'))) }}</td></tr>
    </table>

    <p class="footnote muted">Revenue = verified payments by the date they were confirmed. Stays, booked value and occupancy = approved and completed bookings by stay date.</p>
@endsection
