<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: reimbursements
 *
 * Auto-generated from CSV schema · 30 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->bigIncrements('reimb_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->integer('period_year')->nullable();
            $table->integer('period_month')->nullable();
            $table->string('bill_no')->nullable();
            $table->date('bill_date')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_gstin')->nullable();
            $table->text('description')->nullable();
            $table->decimal('claim_amount', 15, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('claimable_amount', 15, 2)->default(0);
            $table->decimal('tax_treatment', 15, 2)->default(0);
            $table->string('exemption_section')->nullable();
            $table->string('doc_path')->nullable();
            $table->string('doc_size_kb')->nullable();
            $table->unsignedBigInteger('approver_emp_id')->nullable()->index(); // FK -> employees
            $table->string('approver_name')->nullable();
            $table->string('approval_status')->nullable();
            $table->date('approval_date')->nullable();
            $table->string('posting_run_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('utr_no')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
