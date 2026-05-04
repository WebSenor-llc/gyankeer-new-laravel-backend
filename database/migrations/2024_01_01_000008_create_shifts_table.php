<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: shifts
 *
 * Auto-generated from CSV schema · 25 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->bigIncrements('shift_id');
            $table->string('shift_code')->nullable();
            $table->string('shift_name')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('total_hours', 15, 2)->default(0);
            $table->string('break_minutes')->nullable();
            $table->string('net_hours')->nullable();
            $table->string('weekly_pattern')->nullable();
            $table->decimal('weekly_off_days', 15, 2)->default(0);
            $table->string('applicable_locations')->nullable();
            $table->string('applicable_genders')->nullable();
            $table->boolean('night_shift_flag')->default(false);
            $table->boolean('female_with_consent_required')->default(false);
            $table->boolean('transport_provided')->default(false);
            $table->boolean('ot_eligible')->default(false);
            $table->integer('max_ot_hours_per_qtr')->nullable();
            $table->string('grace_minutes_late')->nullable();
            $table->string('grace_minutes_early_out')->nullable();
            $table->string('min_hours_for_full_day')->nullable();
            $table->boolean('attendance_required')->default(false);
            $table->boolean('gps_clock_in_allowed')->default(false);
            $table->string('color_code')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
