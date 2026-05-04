<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the EXACT counts the user entered in the SUGAM-style /attendance/counts
 * page (P, W, CL, SL, PL, A, HD, OT — all decimal, supporting half-day fractions).
 *
 * One row per (emp × period). The calendar-day distribution still happens in
 * attendance_daily for the day-by-day grid, but the user's intent (e.g. "25.5
 * present + 0.5 CL") is preserved here verbatim and used by the payroll engine
 * to compute paid days / LOP correctly.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_summary', function (Blueprint $table) {
            $table->bigIncrements('summary_id');
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('emp_id')->nullable()->index();
            $table->integer('period_year')->index();
            $table->integer('period_month')->index();

            $table->decimal('p_count',  6, 2)->default(0);   // Present (incl. half: 25.5)
            $table->decimal('w_count',  6, 2)->default(0);   // Weekly Off
            $table->decimal('cl_count', 6, 2)->default(0);   // Casual Leave (0.5 ok)
            $table->decimal('sl_count', 6, 2)->default(0);   // Sick Leave  (0.5 ok)
            $table->decimal('pl_count', 6, 2)->default(0);   // Paid Leave  (0.5 ok)
            $table->decimal('a_count',  6, 2)->default(0);   // Absent
            $table->decimal('hd_count', 6, 2)->default(0);   // Explicit Half-Day cells
            $table->decimal('ot_hours', 8, 2)->default(0);   // Overtime hours
            $table->decimal('total_days', 6, 2)->default(0); // Sum (= month total when valid)

            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['emp_id', 'period_year', 'period_month'], 'attsum_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_summary');
    }
};
