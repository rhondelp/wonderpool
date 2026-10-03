{{-- Test email from Settings → Notifications (Notifications\TestEmail). Markdown: keep unindented. --}}
<x-mail::message>
# It works!

This test email from {{ $resort }} was sent through the queue at {{ $sentAt->format('M j, Y g:i A') }}.

If you can read this, guests will receive booking emails too (for the types switched on in Settings → Notifications).
</x-mail::message>
