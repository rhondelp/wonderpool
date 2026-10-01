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
    case Content = 'content';
    case Seo = 'seo';

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
            self::Content => 'Website content',
            self::Seo => 'SEO',
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
            self::Content => 'document-text',
            self::Seo => 'magnifying-glass',
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
            self::Content => [
                'content.tagline' => ['label' => 'Tagline', 'type' => 'text', 'default' => 'Pools · Gardens · Good times', 'rules' => ['nullable', 'string', 'max:120'], 'hint' => 'Short line shown in the footer and under the logo.'],
                'content.hero_title' => ['label' => 'Home headline', 'type' => 'text', 'default' => 'Your private pool and garden escape', 'rules' => ['required', 'string', 'max:120']],
                'content.hero_subtitle' => ['label' => 'Home sub-headline', 'type' => 'textarea', 'default' => 'Book the whole resort for your family day, birthday or company outing. Day, night and 24-hour packages.', 'rules' => ['nullable', 'string', 'max:300']],
                'content.about' => ['label' => 'About the resort', 'type' => 'textarea', 'default' => '', 'rules' => ['nullable', 'string', 'max:3000'], 'hint' => 'Shown on the home page. Leave blank to hide. Supports **bold**, lists (- item) and line breaks.'],
                'content.highlights' => ['label' => 'Highlights', 'type' => 'textarea', 'default' => "Exclusive use: the whole resort is yours\nAdult and kiddie pools\nFunction hall for up to 50 guests\nFree parking", 'rules' => ['nullable', 'string', 'max:1000'], 'hint' => 'One highlight per line (up to 6 are shown). Leave blank to hide.'],
                'content.house_rules' => ['label' => 'House rules', 'type' => 'textarea', 'default' => "- Proper swimming attire is required in the pools.\n- Children must be supervised by an adult at all times.\n- No glass bottles in the pool area.\n- Please keep the noise down after 10:00 PM.", 'rules' => ['nullable', 'string', 'max:5000'], 'hint' => 'Shown on the Policies page. PLACEHOLDER rules: replace with the resort\'s own. Supports lists (- item).'],
                'content.cancellation_policy' => ['label' => 'Cancellation & reschedule policy', 'type' => 'textarea', 'default' => '', 'rules' => ['nullable', 'string', 'max:5000'], 'hint' => 'Shown on the Policies page and booking summary. Leave blank to hide (PLAN.md §14 Q4).'],
                'content.booking_success_note' => ['label' => 'After booking: next steps', 'type' => 'textarea', 'default' => "We will check your payment proof and confirm your booking within 24 hours. Keep your reference code: you need it with your mobile number to track your booking.", 'rules' => ['nullable', 'string', 'max:2000']],
            ],
            self::Seo => [
                'seo.meta_description' => ['label' => 'Search description', 'type' => 'textarea', 'default' => 'Wonderpool Garden Resort: private pools, gardens and function hall. Check availability and book online.', 'rules' => ['nullable', 'string', 'max:300'], 'hint' => 'Shown by Google and when the site is shared (about 155 characters).'],
                'seo.og_image_url' => ['label' => 'Share image URL', 'type' => 'url', 'default' => '', 'rules' => ['nullable', 'url:https', 'max:2000'], 'hint' => 'Image shown when the site is shared on Facebook. Blank = first gallery photo.'],
            ],
        };
    }
}
