{{-- To guest: booking confirmed (Notifications\BookingApproved). Markdown: keep unindented. --}}
<x-mail::message>
# Your booking is confirmed!

Hi {{ $booking->guest_name }}, great news: your stay at {{ $resort }} is **confirmed**.

@include('mail.bookings._details')

Please arrive on time and bring your reference code. Any remaining balance is settled at the resort.

<x-mail::button :url="route('track')" color="success">
View my booking
</x-mail::button>

See you soon!
</x-mail::message>
