<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: loans_advances
 *
 * Auto-generated from CSV schema · 26 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('loans_advances', function (Blueprint $table) {
            $table->bigIncrements('loan_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->string('loan_type')->nullable();
            $table->decimal('principal', 15, 2)->default(0);
            $table->decimal('interest_rate_pct', 8, 2)->nullable();
            $table->integer('tenure_months')->nullable();
            $table->decimal('emi_amount', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('outstanding_principal', 15, 2)->default(0);
            $table->decimal('outstanding_interest', 15, 2)->default(0);
            $table->decimal('perquisite_taxable', 15, 2)->default(0);
            $table->decimal('perq_amount_annual', 15, 2)->default(0);
            $table->string('perq_section')->nullable();
            $table->date('sanction_date')->nullable();
            $table->string('sanction_doc')->nullable();
            $table->unsignedBigInteger('approver_emp_id')->nullable()->index(); // FK -> employees
            $table->string('approver_name')->nullable();
            $table->string('repayment_status')->nullable();
            $table->date('last_emi_paid_date')->nullable();
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->boolean('active_flag')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans_advances');
    }
};
