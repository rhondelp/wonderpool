{{--
    Booking receipt / confirmation (browser print and PDF via DomPDF, layouts.print).
    Resort info from settings ($site = PublicContentService::site()). Amounts are the booking snapshot.
    Data: Admin\BookingController@receipt. Edit layout/wording here; styles in layouts/print.
--}}
@extends('layouts.print')

@section('title', 'Receipt '.$booking->reference_code)

@php $money = fn (int $cents) => \App\Support\Money::format($cents); @endphp

@section('content')
    @unless ($pdf)
        <div class="toolbar"><button type="button" onclick="window.print()">Print</button></div>
    @endunless

    <div class="row">
        <div>
            <h1>{{ $site['name'] }}</h1>
            @if ($site['address'])<div class="muted">{{ $site['address'] }}</div>@endif
            <div class="muted">{{ collect([$site['phone'], $site['email']])->filter()->implode(' · ') }}</div>
        </div>
        <div class="right">
            <div class="muted">Booking {{ $booking->status === \App\Enums\BookingStatus::Approved || $booking->status === \App\Enums\BookingStatus::Completed ? 'confirmation' : 'summary' }}</div>
            <div class="ref">{{ $booking->reference_code }}</div>
            <span class="badge">{{ $booking->status->label() }}</span>
        </div>
    </div>

    <h2>Guest</h2>
    <table>
        <tr><td class="muted label-col">Name</td><td>{{ $booking->guest_name }}</td></tr>
        <tr><td class="muted">Mobile</td><td>{{ \App\Support\PhoneNumber::display($booking->guest_phone) }}</td></tr>
        @if ($booking->guest_email)<tr><td class="muted">Email</td><td>{{ $booking->guest_email }}</td></tr>@endif
        @if ($booking->event_type)<tr><td class="muted">Occasion</td><td>{{ $booking->event_type }}</td></tr>@endif
    </table>

    <h2>Stay</h2>
    <table>
        <tr><td class="muted label-col">Package</td><td>{{ $booking->package->name }}</td></tr>
        <tr><td class="muted">When</td><td>{{ $windowLabel }}</td></tr>
        <tr><td class="muted">Guests</td><td>{{ $booking->guest_count }}</td></tr>
    </table>

    <h2>Charges</h2>
    <table>
        @php $addOnsTotal = 0; @endphp
        @foreach ($booking->addOns as $addOn)
            @php $line = (int) $addOn->pivot->unit_price_cents * (int) $addOn->pivot->quantity; $addOnsTotal += $line; @endphp
        @endforeach
        <tr><td>{{ $booking->package->name }}</td><td class="num">{{ $money($booking->total_amount_cents - $addOnsTotal) }}</td></tr>
        @foreach ($booking->addOns as $addOn)
            <tr><td>{{ $addOn->name }} × {{ $addOn->pivot->quantity }} @ {{ $money((int) $addOn->pivot->unit_price_cents) }}</td><td class="num">{{ $money((int) $addOn->pivot->unit_price_cents * (int) $addOn->pivot->quantity) }}</td></tr>
        @endforeach
        <tr class="total"><td>Total</td><td class="num">{{ $booking->formatted_total }}</td></tr>
    </table>

    <h2>Payments</h2>
    <table>
        <tr><th>Date</th><th>Type</th><th>Reference</th><th class="num">Amount</th></tr>
        @forelse ($booking->payments->where('status', \App\Enums\PaymentStatus::Verified) as $payment)
            <tr><td>{{ ($payment->verified_at ?? $payment->created_at)?->format('M j, Y') }}</td><td>{{ $payment->type->label() }}</td><td>{{ $payment->reference_no ?? '—' }}</td><td class="num">{{ $payment->formatted_amount }}</td></tr>
        @empty
            <tr><td colspan="4" class="muted">No verified payments yet.</td></tr>
        @endforelse
        <tr><td colspan="3">Total paid</td><td class="num">{{ $money($summary['verified']) }}</td></tr>
        <tr class="total"><td colspan="3">Balance due</td><td class="num">{{ $money($summary['balance_due']) }}</td></tr>
    </table>

    <p class="muted footnote">Issued {{ now()->format('M j, Y g:i A') }}. Please present this reference on arrival. Thank you for choosing {{ $site['name'] }}!</p>
@endsection
