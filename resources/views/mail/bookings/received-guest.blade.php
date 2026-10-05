{{-- To guest: booking request received (Notifications\BookingReceived). Markdown: keep unindented. --}}
<x-mail::message>
# Thank you, {{ $booking->guest_name }}!

We received your booking request at {{ $resort }}. It stays **pending** until we have checked your downpayment.

@include('mail.bookings._details')

**Downpayment to secure your booking:** {{ $details['downpayment'] }}

If you have not sent your payment proof yet, open **Track my booking**, enter your reference code and mobile number, and upload it there.

<x-mail::button :url="route('track')" color="primary">
Track my booking
</x-mail::button>

Keep your reference code **{{ $details['reference'] }}**. We will email you as soon as your booking is confirmed.
</x-mail::message>
