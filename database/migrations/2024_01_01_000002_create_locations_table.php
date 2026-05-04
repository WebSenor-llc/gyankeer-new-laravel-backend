<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: locations
 *
 * Auto-generated from CSV schema · 25 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->bigIncrements('location_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->string('location_code')->nullable();
            $table->string('location_name')->nullable();
            $table->string('location_type')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pin_code')->nullable();
            $table->string('shops_estab_reg_no')->nullable();
            $table->string('factory_license_no')->nullable();
            $table->string('district_labour_office')->nullable();
            $table->integer('headcount')->nullable();
            $table->boolean('workplace_flag_for_employee_master')->default(false);
            $table->string('gps_lat')->nullable();
            $table->string('gps_lng')->nullable();
            $table->integer('geofence_radius_meters')->nullable();
            $table->string('timezone')->nullable();
            $table->decimal('working_days_pattern', 15, 2)->default(0);
            $table->integer('overtime_cap_qtr')->nullable();
            $table->boolean('female_night_shift_allowed')->default(false);
            $table->decimal('allowed_shift_ids', 15, 2)->default(0);
            $table->string('holiday_calendar_id')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
