<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seasonal / weekend / holiday price adjustments. package_id NULL = applies to all packages.
 * adjustment_value = whole percent points (percent) or signed centavos (fixed); see PricingAdjustmentType.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->jsonb('days_of_week')->nullable();
            $table->string('adjustment_type', 20);
            $table->integer('adjustment_value');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'priority']);
            $table->index(['starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
