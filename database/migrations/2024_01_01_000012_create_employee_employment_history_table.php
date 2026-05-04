<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: employee_employment_history
 *
 * Auto-generated from CSV schema · 27 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_employment_history', function (Blueprint $table) {
            $table->bigIncrements('emp_hist_id');
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->integer('sequence_no')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employer_industry')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->decimal('date_from', 15, 2)->default(0);
            $table->decimal('date_to', 15, 2)->default(0);
            $table->integer('duration_months')->nullable();
            $table->decimal('last_ctc', 15, 2)->default(0);
            $table->decimal('last_gross', 15, 2)->default(0);
            $table->decimal('last_basic', 15, 2)->default(0);
            $table->decimal('notice_period_days', 15, 2)->default(0);
            $table->boolean('notice_served_flag')->default(false);
            $table->string('reason_for_leaving')->nullable();
            $table->string('reference_person')->nullable();
            $table->string('reference_phone')->nullable();
            $table->string('reference_email')->nullable();
            $table->string('reliev_letter_doc_id')->nullable();
            $table->string('payslips_doc_id')->nullable();
            $table->string('verification_status')->nullable();
            $table->string('verified_by')->nullable();
            $table->date('verified_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_employment_history');
    }
};
