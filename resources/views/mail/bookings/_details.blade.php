{{-- Booking facts table shared by booking emails (BookingNotification::details()). No phone, email or proof. Markdown: keep unindented. --}}
<x-mail::table>
| Booking | |
|:--------|:--|
| Reference | **{{ $details['reference'] }}** |
| Package | {{ $details['package'] }} |
| Schedule | {{ $details['schedule'] }} |
| Guests | {{ $details['guests'] }} |
| Total | {{ $details['total'] }} |
</x-mail::table>
