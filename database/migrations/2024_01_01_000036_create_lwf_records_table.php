<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: lwf_records
 *
 * Auto-generated from CSV schema · 16 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('lwf_records', function (Blueprint $table) {
            $table->bigIncrements('lwf_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->string('state')->nullable();
            $table->integer('period_year')->nullable();
            $table->string('period_half')->nullable();
            $table->decimal('employee_contribution', 15, 2)->default(0);
            $table->decimal('employer_contribution', 15, 2)->default(0);
            $table->decimal('total_contribution', 15, 2)->default(0);
            $table->string('challan_no')->nullable();
            $table->date('due_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lwf_records');
    }
};
