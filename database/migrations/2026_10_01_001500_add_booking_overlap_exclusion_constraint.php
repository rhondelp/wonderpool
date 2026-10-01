<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Second line of defense against double booking (PLAN.md §5.2, D-021): no two active,
 * non-deleted bookings may overlap. Half-open ranges '[)' let back-to-back windows touch.
 * BookingService's advisory lock remains the primary guard; violations (SQLSTATE 23P01)
 * become SlotUnavailableException. Blocked dates are checked by the service only.
 */
return new class () extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE bookings
            ADD CONSTRAINT bookings_no_overlap
            EXCLUDE USING gist (tsrange(starts_at, ends_at, '[)') WITH &&)
            WHERE (status IN ('pending', 'approved') AND deleted_at IS NULL)
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_no_overlap');
    }
};
