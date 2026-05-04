<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: leave_types
 *
 * Auto-generated from CSV schema · 17 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->bigIncrements('leave_type_id');
            $table->string('leave_code')->nullable();
            $table->string('leave_name')->nullable();
            $table->string('category')->nullable();
            $table->integer('annual_quota')->nullable();
            $table->string('accrual_method')->nullable();
            $table->integer('min_days_per_application')->nullable();
            $table->integer('max_continuous_days')->nullable();
            $table->string('encashable')->nullable();
            $table->integer('carry_forward_max')->nullable();
            $table->integer('eligibility_after_doj_months')->nullable();
            $table->string('applies_to_genders')->nullable();
            $table->integer('requires_medical_certificate_after_days')->nullable();
            $table->string('paid_unpaid')->nullable();
            $table->string('statutory_act')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
