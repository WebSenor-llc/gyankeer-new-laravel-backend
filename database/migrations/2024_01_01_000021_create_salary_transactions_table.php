<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: salary_transactions
 *
 * Auto-generated from CSV schema · 26 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_transactions', function (Blueprint $table) {
            $table->bigIncrements('txn_id');
            $table->unsignedBigInteger('run_id')->nullable()->index(); // FK -> salary_runs
            $table->integer('period_year')->nullable();
            $table->integer('period_month')->nullable();
            $table->date('txn_date')->nullable();
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->string('txn_type')->nullable();
            $table->string('component_code')->nullable();
            $table->string('component_name')->nullable();
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->string('currency')->nullable();
            $table->string('gl_account_code')->nullable();
            $table->string('gl_account_name')->nullable();
            $table->string('ledger_reference')->nullable();
            $table->string('source_doc_id')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('department')->nullable();
            $table->unsignedBigInteger('approver_emp_id')->nullable()->index(); // FK -> employees
            $table->string('status')->nullable();
            $table->boolean('reversed_flag')->default(false);
            $table->unsignedBigInteger('reversal_txn_id')->nullable()->index(); // FK -> salary_transactions
            $table->string('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_transactions');
    }
};
