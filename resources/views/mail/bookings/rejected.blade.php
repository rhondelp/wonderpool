{{-- To guest: booking request declined, with reason (Notifications\BookingRejected). Markdown: keep unindented. --}}
<x-mail::message>
# We could not confirm your booking

Hi {{ $booking->guest_name }}, we are sorry, but we could not accept your booking request **{{ $details['reference'] }}**.

@if ($reason)
<x-mail::panel>
**Reason:** {{ $reason }}
</x-mail::panel>
@endif

@include('mail.bookings._details')

If you have questions or would like another date, please contact us using the details below. You are welcome to book again on our website.

<x-mail::button :url="route('book')" color="primary">
Check other dates
</x-mail::button>
</x-mail::message>
