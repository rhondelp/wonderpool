<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Default business settings (keys documented in HISTORY.md "Settings Keys").
 * Contact/social/payment values are PLACEHOLDERS until the owner supplies real ones (PLAN.md §14).
 * Existing values are never overwritten, so re-seeding keeps admin edits.
 */
class SettingSeeder extends Seeder
{
    /**
     * Inserts missing keys only.
     */
    public function run(): void
    {
        $settings = [
            'general' => [
                'general.resort_name' => 'Wonderpool Garden Resort',
            ],
            'booking' => [
                'booking.downpayment_percent' => '50',
                'booking.pending_hold_hours' => '24',
                'booking.lead_time_hours' => '24',
                'booking.max_advance_days' => '365',
            ],
            'payment' => [
                'payment.instructions' => "Send your downpayment via GCash or bank transfer, then upload a screenshot of the receipt.\nGCash: 09XX XXX XXXX (Account name: TO BE PROVIDED)\nBank: TO BE PROVIDED",
            ],
            'contact' => [
                'contact.phone' => '+63 9XX XXX XXXX',
                'contact.email' => 'info@example.com',
                'contact.address' => 'Address to be provided',
                'contact.map_embed_url' => '',
            ],
            'social' => [
                'social.facebook_url' => 'https://www.facebook.com/',
                'social.instagram_url' => '',
            ],
        ];

        foreach ($settings as $group => $pairs) {
            foreach ($pairs as $key => $value) {
                Setting::query()->firstOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
            }
        }
    }
}
