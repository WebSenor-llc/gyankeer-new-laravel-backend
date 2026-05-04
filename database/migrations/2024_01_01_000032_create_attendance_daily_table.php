<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: attendance_daily
 *
 * Auto-generated from CSV schema · 27 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_daily', function (Blueprint $table) {
            $table->bigIncrements('attn_id');
            $table->date('attn_date')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable()->index(); // FK -> shifts
            $table->string('shift_name')->nullable();
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
            $table->string('hours_worked')->nullable();
            $table->string('ot_hours')->nullable();
            $table->boolean('late_flag')->default(false);
            $table->decimal('late_minutes', 8, 2)->nullable();
            $table->boolean('early_out_flag')->default(false);
            $table->decimal('early_minutes', 8, 2)->nullable();
            $table->string('status')->nullable();
            $table->string('status_reason')->nullable();
            $table->string('source')->nullable();
            $table->string('device_id')->nullable();
            $table->string('location_lat')->nullable();
            $table->string('location_lng')->nullable();
            $table->string('geofence_match')->nullable();
            $table->decimal('biometric_match_score', 8, 2)->nullable();
            $table->unsignedBigInteger('approver_emp_id')->nullable()->index(); // FK -> employees
            $table->string('approval_status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_daily');
    }
};
