<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add-ons chosen for a booking, with the unit price snapshotted in centavos (D-001).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('booking_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price_cents');
            $table->timestamps();

            $table->unique(['booking_id', 'add_on_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_add_ons');
    }
};
