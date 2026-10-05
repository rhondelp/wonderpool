<?php

namespace App\Notifications\Channels;

use App\Enums\NotificationType;

/**
 * Decides which channels a notification goes through (D-037). Notifications call this from via(),
 * so listeners and services never name a channel. Channels come from
 * config('wonderpool.notifications.channels') and are dropped when the recipient has no route
 * for them (e.g. no email address). Adding SMS later = a channel class + config entry
 * (docs/architecture.md "Notifications").
 */
class NotificationChannelResolver
{
    /**
     * Channels for one notification type and recipient.
     *
     * @param  NotificationType|null  $type  Null for system mail without a type (test email): default channels
     * @param  object  $notifiable  User or AnonymousNotifiable
     * @return list<string>
     */
    public function channels(?NotificationType $type, object $notifiable): array
    {
        /** @var array<string, list<string>> $map */
        $map = (array) config('wonderpool.notifications.channels', []);
        $channels = ($type !== null ? ($map[$type->value] ?? null) : null) ?? $map['default'] ?? ['mail'];

        return array_values(array_filter($channels, fn (string $channel): bool => $this->canReceive($notifiable, $channel)));
    }

    /**
     * Whether the recipient has an address for the channel.
     */
    private function canReceive(object $notifiable, string $channel): bool
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return false;
        }

        $route = $notifiable->routeNotificationFor($channel);

        return $route !== null && $route !== '' && $route !== [];
    }
}
