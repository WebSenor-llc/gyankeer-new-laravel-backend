<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: bonus_provisions
 *
 * Auto-generated from CSV schema · 22 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('bonus_provisions', function (Blueprint $table) {
            $table->bigIncrements('bonus_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->integer('fy')->nullable();
            $table->boolean('eligible_flag')->default(false);
            $table->unsignedBigInteger('salary_group_id')->nullable()->index(); // FK -> salary_groups
            $table->decimal('monthly_basic_da', 15, 2)->default(0);
            $table->decimal('bonus_wage_capped_7k', 15, 2)->default(0);
            $table->decimal('bonus_percent', 8, 2)->nullable();
            $table->integer('months_worked')->nullable();
            $table->decimal('annual_bonus_amount', 15, 2)->default(0);
            $table->decimal('monthly_provision', 15, 2)->default(0);
            $table->decimal('already_provisioned_ytd', 15, 2)->default(0);
            $table->decimal('balance_to_provision', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->string('paid_via')->nullable();
            $table->string('ref_doc_no')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_provisions');
    }
};
