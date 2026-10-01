<?php

namespace App\Enums;

/**
 * Settings screen groups. Each group owns a set of dotted keys ("group.name");
 * field definitions (label, input type, default, validation) live in fields().
 * To add a setting: add it to fields() here, then document it in HISTORY.md "Settings Keys".
 */
enum SettingGroup: string
{
    case General = 'general';
    case Booking = 'booking';
    case Payment = 'payment';
    case Contact = 'contact';
    case Social = 'social';

    /**
     * Tab label on the settings screen.
     */
    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Booking => 'Booking rules',
            self::Payment => 'Payment',
            self::Contact => 'Contact',
            self::Social => 'Social links',
        };
    }

    /**
     * Heroicon (outline) shown on the settings tab.
     */
    public function icon(): string
    {
        return match ($this) {
            self::General => 'building-office',
            self::Booking => 'calendar-days',
            self::Payment => 'banknotes',
            self::Contact => 'phone',
            self::Social => 'share',
        };
    }

    /**
     * Field definitions keyed by full setting key. `type` is the input type: text|number|email|url|textarea.
     *
     * @return array<string, array{label: string, type: string, default: string, rules: list<string>, hint?: string}>
     */
    public function fields(): array
    {
        return match ($this) {
            self::General => [
                'general.resort_name' => ['label' => 'Resort name', 'type' => 'text', 'default' => 'Wonderpool Garden Resort', 'rules' => ['required', 'string', 'max:100']],
            ],
            self::Booking => [
                'booking.downpayment_percent' => ['label' => 'Downpayment (%)', 'type' => 'number', 'default' => '50', 'rules' => ['required', 'integer', 'min:0', 'max:100'], 'hint' => 'Share of the total the guest pays to secure a booking.'],
                'booking.pending_hold_hours' => ['label' => 'Pending hold (hours)', 'type' => 'number', 'default' => '24', 'rules' => ['required', 'integer', 'min:1', 'max:168'], 'hint' => 'Unpaid pending bookings expire after this many hours.'],
                'booking.lead_time_hours' => ['label' => 'Minimum lead time (hours)', 'type' => 'number', 'default' => '24', 'rules' => ['required', 'integer', 'min:0', 'max:720'], 'hint' => 'How far ahead of the start time guests must book.'],
                'booking.max_advance_days' => ['label' => 'Maximum advance booking (days)', 'type' => 'number', 'default' => '365', 'rules' => ['required', 'integer', 'min:1', 'max:730']],
            ],
            self::Payment => [
                'payment.instructions' => ['label' => 'Payment instructions', 'type' => 'textarea', 'default' => "Send your downpayment via GCash or bank transfer, then upload a screenshot of the receipt.\nGCash: 09XX XXX XXXX (Account name: TO BE PROVIDED)\nBank: TO BE PROVIDED", 'rules' => ['required', 'string', 'max:2000'], 'hint' => 'Shown to guests on the payment step (GCash / bank details).'],
            ],
            self::Contact => [
                'contact.phone' => ['label' => 'Phone', 'type' => 'text', 'default' => '+63 9XX XXX XXXX', 'rules' => ['required', 'string', 'max:30']],
                'contact.email' => ['label' => 'Email', 'type' => 'email', 'default' => 'info@example.com', 'rules' => ['required', 'email', 'max:255']],
                'contact.address' => ['label' => 'Address', 'type' => 'textarea', 'default' => 'Address to be provided', 'rules' => ['required', 'string', 'max:500']],
                'contact.map_embed_url' => ['label' => 'Google Maps embed URL', 'type' => 'url', 'default' => '', 'rules' => ['nullable', 'url:https', 'max:2000'], 'hint' => 'Google Maps → Share → Embed a map → copy the src URL.'],
            ],
            self::Social => [
                'social.facebook_url' => ['label' => 'Facebook page', 'type' => 'url', 'default' => 'https://www.facebook.com/', 'rules' => ['nullable', 'url:https', 'max:255']],
                'social.instagram_url' => ['label' => 'Instagram page', 'type' => 'url', 'default' => '', 'rules' => ['nullable', 'url:https', 'max:255']],
            ],
        };
    }
}
