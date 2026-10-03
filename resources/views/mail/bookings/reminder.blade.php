{{-- To guest: reminder before the stay (Notifications\BookingReminder via bookings:send-reminders). Markdown: keep unindented. --}}
<x-mail::message>
# See you soon, {{ $booking->guest_name }}!

This is a friendly reminder of your upcoming stay at {{ $resort }}.

@include('mail.bookings._details')

Please bring your reference code **{{ $details['reference'] }}**. Any remaining balance is settled at the resort.

<x-mail::button :url="route('policies')" color="primary">
House rules
</x-mail::button>

We look forward to welcoming you!
</x-mail::message>
