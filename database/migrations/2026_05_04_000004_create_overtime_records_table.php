<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-employee monthly overtime entries — matches SUGAM HR's "Add Overtime"
 * form. The payroll engine reads this and adds OT amount to gross_earnings.
 *
 *   ot_amount = gross / 30 / 8  *  ot_rate  *  ot_hours
 *   (standard ₹/hour wage × multiplier × hours)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('overtime_records', function (Blueprint $table) {
            $table->bigIncrements('ot_id');
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('emp_id')->nullable()->index();
            $table->integer('period_year')->index();
            $table->integer('period_month')->index();
            $table->decimal('ot_rate', 4, 2)->default(2.00);   // 1.5x, 2x, etc.
            $table->decimal('ot_hours', 6, 2)->default(0);
            $table->decimal('ot_amount', 12, 2)->default(0);   // computed on save
            $table->string('notes', 500)->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['emp_id','period_year','period_month'], 'ot_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_records');
    }
};
