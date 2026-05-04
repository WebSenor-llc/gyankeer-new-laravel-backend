<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: salary_runs
 *
 * Auto-generated from CSV schema · 38 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_runs', function (Blueprint $table) {
            $table->bigIncrements('run_id');
            $table->string('run_code')->nullable();
            $table->integer('period_year')->nullable();
            $table->integer('period_month')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->string('status')->nullable();
            $table->decimal('eligible_emp_count', 15, 2)->default(0);
            $table->decimal('locked_emp_count', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net_payout', 15, 2)->default(0);
            $table->decimal('total_employer_cost', 15, 2)->default(0);
            $table->decimal('total_pf_emp', 15, 2)->default(0);
            $table->decimal('total_pf_er', 15, 2)->default(0);
            $table->decimal('total_eps', 15, 2)->default(0);
            $table->decimal('total_edli', 15, 2)->default(0);
            $table->decimal('total_admin', 15, 2)->default(0);
            $table->decimal('total_esi_emp', 15, 2)->default(0);
            $table->decimal('total_esi_er', 15, 2)->default(0);
            $table->decimal('total_pt', 15, 2)->default(0);
            $table->decimal('total_lwf_emp', 15, 2)->default(0);
            $table->decimal('total_lwf_er', 15, 2)->default(0);
            $table->decimal('total_tds', 15, 2)->default(0);
            $table->decimal('total_bonus_provision', 15, 2)->default(0);
            $table->decimal('total_gratuity_provision', 15, 2)->default(0);
            $table->decimal('total_arrears', 15, 2)->default(0);
            $table->decimal('total_incentive', 15, 2)->default(0);
            $table->timestamp('run_started_at')->nullable();
            $table->timestamp('calc_completed_at')->nullable();
            $table->timestamp('hr_approved_at')->nullable();
            $table->timestamp('finance_approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('bank_file_generated_at')->nullable();
            $table->decimal('total_disbursed', 15, 2)->default(0);
            $table->text('approval_chain')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_runs');
    }
};
