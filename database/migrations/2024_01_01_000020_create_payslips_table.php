<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: payslips
 *
 * Auto-generated from CSV schema · 56 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->bigIncrements('payslip_id');
            $table->unsignedBigInteger('run_id')->nullable()->index(); // FK -> salary_runs
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->integer('period_year')->nullable();
            $table->integer('period_month')->nullable();
            $table->string('payable_days')->nullable();
            $table->decimal('lop_days', 15, 2)->default(0);
            $table->integer('present_days')->nullable();
            $table->decimal('weekly_off_days', 15, 2)->default(0);
            $table->string('holidays')->nullable();
            $table->decimal('basic', 15, 2)->default(0);
            $table->decimal('hra', 15, 2)->default(0);
            $table->decimal('da', 15, 2)->default(0);
            $table->decimal('conveyance', 15, 2)->default(0);
            $table->decimal('medical', 15, 2)->default(0);
            $table->decimal('spl_allow', 15, 2)->default(0);
            $table->string('lta')->nullable();
            $table->string('fbp')->nullable();
            $table->decimal('ot_amount', 15, 2)->default(0);
            $table->string('incentive')->nullable();
            $table->decimal('bonus', 15, 2)->default(0);
            $table->decimal('arrear', 15, 2)->default(0);
            $table->decimal('reimbursement', 15, 2)->default(0);
            $table->string('leave_encashment')->nullable();
            $table->decimal('gross_earnings', 15, 2)->default(0);
            $table->decimal('epf_emp', 15, 2)->default(0);
            $table->decimal('esi_emp', 15, 2)->default(0);
            $table->string('pt')->nullable();
            $table->decimal('lwf_emp', 15, 2)->default(0);
            $table->decimal('tds', 15, 2)->default(0);
            $table->decimal('loan_emi', 15, 2)->default(0);
            $table->string('advance_recovery')->nullable();
            $table->string('fine_recovery')->nullable();
            $table->string('post_deduction')->nullable();
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);
            $table->decimal('employer_pf', 15, 2)->default(0);
            $table->decimal('eps', 15, 2)->default(0);
            $table->decimal('edli', 15, 2)->default(0);
            $table->decimal('pf_admin', 15, 2)->default(0);
            $table->decimal('employer_esi', 15, 2)->default(0);
            $table->decimal('gratuity_provision', 15, 2)->default(0);
            $table->decimal('lwf_employer', 15, 2)->default(0);
            $table->decimal('total_employer_cost', 15, 2)->default(0);
            $table->unsignedBigInteger('bank_id')->nullable()->index(); // FK -> banks
            $table->string('bank_account')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('disbursement_mode')->nullable();
            $table->string('utr_no')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->string('disbursement_status')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->string('signed_dsc_serial')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('whatsapp_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
