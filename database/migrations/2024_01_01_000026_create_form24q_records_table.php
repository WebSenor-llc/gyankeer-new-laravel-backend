<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: form24q_records
 *
 * Auto-generated from CSV schema · 20 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('form24q_records', function (Blueprint $table) {
            $table->bigIncrements('form24q_id');
            $table->integer('fy')->nullable();
            $table->string('quarter')->nullable();
            $table->string('tan')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->string('deductee_pan')->nullable();
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->decimal('total_gross_paid', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);
            $table->decimal('total_tax_paid', 15, 2)->default(0);
            $table->string('section_code')->nullable();
            $table->string('fvu_filename')->nullable();
            $table->date('filing_date')->nullable();
            $table->string('filing_ack_no')->nullable();
            $table->string('token_no')->nullable();
            $table->string('traces_status')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form24q_records');
    }
};
