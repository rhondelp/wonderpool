<?php

namespace Database\Seeders;

use App\Enums\SettingGroup;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

/**
 * Default business settings, taken from SettingGroup::fields() (keys documented in HISTORY.md "Settings Keys").
 * Contact/social/payment values are PLACEHOLDERS until the owner supplies real ones (PLAN.md §14).
 * Existing values are never overwritten, so re-seeding keeps admin edits.
 */
class SettingSeeder extends Seeder
{
    /**
     * Inserts missing keys only, then clears the settings cache.
     */
    public function run(SettingService $settings): void
    {
        foreach (SettingGroup::cases() as $group) {
            foreach ($group->fields() as $key => $field) {
                Setting::query()->firstOrCreate(['key' => $key], ['value' => $field['default'], 'group' => $group->value]);
            }
        }

        $settings->flush();
    }
}
