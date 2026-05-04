<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: pt_records
 *
 * Auto-generated from CSV schema · 15 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('pt_records', function (Blueprint $table) {
            $table->bigIncrements('pt_id');
            $table->integer('period_year')->nullable();
            $table->integer('period_month')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->string('state')->nullable();
            $table->string('slab_applied')->nullable();
            $table->decimal('pt_amount', 15, 2)->default(0);
            $table->string('paid_via_challan')->nullable();
            $table->string('challan_no')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pt_records');
    }
};
