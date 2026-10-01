<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Support\Money;
use Illuminate\Database\Seeder;

/**
 * Default packages from PLAN.md §1. All values are editable in admin afterwards.
 * Day hours (7AM–5PM) and Day max pax (50) are proposed defaults pending owner confirmation.
 */
class PackageSeeder extends Seeder
{
    /**
     * Upserts by package code.
     */
    public function run(): void
    {
        $packages = [
            [
                'code' => 'DAY-A',
                'name' => 'Day Package (A)',
                'description' => 'Daytime exclusive use of the resort: pools, cottages and function hall.',
                'base_price_cents' => Money::fromPesos(7000),
                'start_time' => '07:00:00',
                'end_time' => '17:00:00',
                'crosses_midnight' => false,
                'max_pax' => 50,
                'sort_order' => 1,
            ],
            [
                'code' => 'NIGHT-D',
                'name' => 'Night Package (D)',
                'description' => 'Overnight exclusive use from 7:00 PM to 5:00 AM the next day.',
                'base_price_cents' => Money::fromPesos(9000),
                'start_time' => '19:00:00',
                'end_time' => '05:00:00',
                'crosses_midnight' => true,
                'max_pax' => 50,
                'sort_order' => 2,
            ],
            [
                'code' => '24H',
                'name' => '24-Hour Package',
                'description' => 'Full-day and overnight exclusive use from 7:00 AM to 5:00 AM the next day.',
                'base_price_cents' => Money::fromPesos(15000),
                'start_time' => '07:00:00',
                'end_time' => '05:00:00',
                'crosses_midnight' => true,
                'max_pax' => 50,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            Package::query()->updateOrCreate(['code' => $package['code']], $package + ['is_active' => true]);
        }
    }
}
