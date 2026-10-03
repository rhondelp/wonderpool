{{-- To owners: payment proof uploaded (Notifications\PaymentProofReceived). Never link the proof file itself (D-032). Markdown: keep unindented. --}}
<x-mail::message>
# Payment proof to review

**{{ $guestName }}** uploaded a payment proof for booking **{{ $details['reference'] }}**.

@include('mail.bookings._details')

**Downpayment required:** {{ $details['downpayment'] }}

Sign in to view the proof, then approve the booking or reject the payment.

<x-mail::button :url="$adminUrl" color="primary">
Review booking
</x-mail::button>
</x-mail::message>
