<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest bookings. starts_at/ends_at store the resolved [start, end) window used for
 * overlap checks; amounts are snapshotted in centavos (PLAN.md §4, D-001).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code', 20)->unique();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->string('guest_name');
            $table->string('guest_phone', 20);
            $table->string('guest_email')->nullable();
            $table->string('event_type', 100)->nullable();
            $table->unsignedSmallInteger('guest_count');
            $table->text('notes')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('total_amount_cents');
            $table->unsignedInteger('downpayment_required_cents');
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['starts_at', 'ends_at']);
            $table->index('status');
            $table->index('guest_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
