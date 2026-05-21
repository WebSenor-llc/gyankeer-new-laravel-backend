<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * leave_ledger — one row per (emp × leave_code × period_year × period_month).
 *
 *   • days_consumed: how many leave days of that code the employee used in
 *     that month. Comes from attendance_summary at payslip-compute time.
 *
 *   • source: 'payroll' (auto from monthly compute) | 'manual' (HR override).
 *
 * The closing_balance on `leave_balances` is recomputed as:
 *     opening + accrued_ytd  −  SUM(ledger.days_consumed for current FY)
 *
 * This design makes the monthly decrement IDEMPOTENT: re-generating salary
 * for the same period only updates the same ledger row, it doesn't double-
 * deduct from the balance.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_ledger', function (Blueprint $t) {
            $t->bigIncrements('ledger_id');
            $t->unsignedBigInteger('emp_id')->index();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->string('leave_code', 10);                 // PL / CL / SL / EL
            $t->integer('period_year');                   // calendar year of the wage month
            $t->integer('period_month');                  // 1..12
            $t->decimal('days_consumed', 8, 2)->default(0);
            $t->string('source', 20)->default('payroll'); // payroll / manual / sync
            $t->text('notes')->nullable();
            $t->timestamps();

            // One row per emp × code × period, so payroll re-runs UPSERT
            // instead of stacking duplicates.
            $t->unique(['emp_id', 'leave_code', 'period_year', 'period_month'], 'ledger_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_ledger');
    }
};
