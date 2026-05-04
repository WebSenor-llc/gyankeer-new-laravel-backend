<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores SUGAM-style line-item deductions added manually by HR for a given
 * (employee × period). One row per employee per month. The amounts here
 * override / append to the auto-computed payslip values.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('manual_deductions', function (Blueprint $table) {
            $table->bigIncrements('manual_ded_id');
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('emp_id')->nullable()->index();
            $table->integer('period_year')->index();
            $table->integer('period_month')->index();
            $table->unsignedBigInteger('payslip_id')->nullable()->index();

            // SUGAM HR deduction line items
            $table->decimal('advance_deduction',     15, 2)->default(0);
            $table->decimal('loan_deduction',        15, 2)->default(0);
            $table->decimal('ag_donation',           15, 2)->default(0);
            $table->decimal('maintenance_charge',    15, 2)->default(0);
            $table->decimal('mobile_deduction',      15, 2)->default(0);
            $table->decimal('canteen_deduction',     15, 2)->default(0);
            $table->decimal('tds_deduction',         15, 2)->default(0);
            $table->decimal('incentive_hours',       15, 2)->default(0);
            $table->decimal('misc_deduction',        15, 2)->default(0);
            $table->decimal('rent_meridian',         15, 2)->default(0);

            $table->text('remarks')->nullable();
            $table->boolean('tds_override_flag')->default(false); // true = override system TDS

            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['emp_id','period_year','period_month'], 'manual_ded_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_deductions');
    }
};
