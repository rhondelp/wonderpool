{{-- To owners: new booking request (Notifications\BookingReceived, forOwner). Markdown: keep unindented. --}}
<x-mail::message>
# New booking request

**{{ $guestName }}** sent a booking request on the website.

@include('mail.bookings._details')

**Downpayment required:** {{ $details['downpayment'] }}

<x-mail::button :url="$adminUrl" color="primary">
Open booking
</x-mail::button>

You will get another email when the guest uploads a payment proof.
</x-mail::message>
