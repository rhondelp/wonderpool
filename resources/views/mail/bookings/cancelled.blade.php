{{-- To guest: booking cancelled, or expired without payment (Notifications\BookingCancelled). Markdown: keep unindented. --}}
<x-mail::message>
@if ($expired)
# Your booking request has expired
@else
# Your booking has been cancelled
@endif

Hi {{ $booking->guest_name }}, your booking **{{ $details['reference'] }}** at {{ $resort }} is now **cancelled**.

@if ($reason)
<x-mail::panel>
**Reason:** {{ $reason }}
</x-mail::panel>
@endif

@include('mail.bookings._details')

@if ($expired)
The date is open again for other guests. If you still want to come, please make a new booking and send your payment proof right away.
@else
If you think this is a mistake or want to talk about a refund or a new date, please contact us using the details below.
@endif

<x-mail::button :url="route('book')" color="primary">
Book again
</x-mail::button>
</x-mail::message>
