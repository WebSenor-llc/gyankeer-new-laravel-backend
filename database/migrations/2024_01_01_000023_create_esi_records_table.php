<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: esi_records
 *
 * Auto-generated from CSV schema · 19 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('esi_records', function (Blueprint $table) {
            $table->bigIncrements('esi_id');
            $table->integer('period_year')->nullable();
            $table->integer('period_month')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('ip_no')->nullable();
            $table->string('member_name')->nullable();
            $table->string('dispensary')->nullable();
            $table->decimal('gross_wage', 15, 2)->default(0);
            $table->integer('days_worked')->nullable();
            $table->string('ee_0_75')->nullable();
            $table->string('er_3_25')->nullable();
            $table->decimal('total_contribution', 15, 2)->default(0);
            $table->string('challan_no')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('tracking_id')->nullable();
            $table->string('filed_status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esi_records');
    }
};
