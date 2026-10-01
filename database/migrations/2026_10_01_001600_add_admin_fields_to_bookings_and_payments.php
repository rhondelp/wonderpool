<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin booking management (M6): booking source/creator, owner price override,
 * cancellation reason; payment notes, recorder and rejection reason.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('source', 20)->default('guest');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('original_total_cents')->nullable();
            $table->text('price_override_reason')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->index('source');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn(['notes', 'rejection_reason']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['source', 'original_total_cents', 'price_override_reason', 'cancellation_reason']);
        });
    }
};
