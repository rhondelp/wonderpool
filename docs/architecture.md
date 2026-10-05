# Architecture

> Purpose: how the application is structured (layers, folders, request flow, components) and why.

## Overview

- Laravel 12 + Blade, PostgreSQL 15+ (D-014), Tailwind CSS v4, Alpine.js. Data access via Eloquent/query builder only; raw SQL, when unavoidable, uses PostgreSQL syntax (see CLAUDE.md database rules).

## Layers (Controllers → Form Requests → Services → Models)
## Folder structure
## Front-end (Blade components, Tailwind tokens, Alpine)
## Cross-cutting concerns (auth, authorization, logging, notifications)

### Notifications (M8)
Flow: service writes + fires an event → `Listeners\SendBookingNotifications` (runs only after the
transaction commits) → `Services\NotificationService` (who receives it, is it switched on?) →
`Notifications\*` (queued; `via()` asks `Notifications\Channels\NotificationChannelResolver`) → channel.

| Event (fired by) | Notification | To |
|---|---|---|
| `BookingCreated` (BookingService::create, guest bookings only) | BookingReceived | guest + active owners |
| `BookingStatusChanged` → approved | BookingApproved | guest |
| `BookingStatusChanged` → rejected | BookingRejected (reason) | guest |
| `BookingStatusChanged` → cancelled / expired | BookingCancelled (reason) | guest |
| `PaymentProofUploaded` (PaymentProofService::store) | PaymentProofReceived | active owners |
| `bookings:send-reminders` (daily) | BookingReminder | guest, once (`reminded_at`) |

- Each type has a switch (`NotificationType::settingKey()`, Settings → Notifications). Guests are
  on-demand recipients (`Notification::route('mail', guest_email)`); no email address = no email.
- All notifications implement `ShouldQueue` + `afterCommit()`, retry 3 times, then `failed()` writes
  `mail.failed` to the activity log (recipient address masked).
- Templates: `resources/views/mail/bookings/*.blade.php` (Markdown), branded components in
  `resources/views/mail/{html,text}` and theme `mail/html/themes/wonderpool.css` (config/mail.php
  `markdown`). Header/footer get `$brand` (name, logo, contact) from a view composer in AppServiceProvider.
- Emails never contain payment-proof links, phone numbers or internal notes.

#### Adding a channel (e.g. SMS) later
1. Create `app/Notifications/Channels/SmsChannel.php` with `send(object $notifiable, Notification $notification)`
   that reads `$notification->toSms($notifiable)` and calls the provider's API (credentials in `.env` + `config/services.php`).
2. Add a `toSms(object $notifiable): string` method to the notifications that should go out by SMS
   (or to `BookingNotification` for all of them).
3. Give recipients a route: `routeNotificationForSms()` on `User`, and `->route('sms', $booking->guest_phone)`
   in `NotificationService::guest()`.
4. Register the name in `AppServiceProvider::boot()`:
   `Notification::extend('sms', fn ($app) => $app->make(\App\Notifications\Channels\SmsChannel::class));`
5. List it in `config/wonderpool.php` → `notifications.channels`, e.g. `'booking_approved' => ['mail', 'sms']`
   (or `'default' => ['mail', 'sms']`). The resolver drops channels a recipient has no route for,
   so listeners and services do not change.
