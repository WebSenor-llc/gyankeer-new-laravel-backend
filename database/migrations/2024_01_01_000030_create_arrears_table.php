<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: arrears
 *
 * Auto-generated from CSV schema · 34 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('arrears', function (Blueprint $table) {
            $table->bigIncrements('arrear_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->string('arrear_type')->nullable();
            $table->string('from_period_year')->nullable();
            $table->string('from_period_month')->nullable();
            $table->string('to_period_year')->nullable();
            $table->string('to_period_month')->nullable();
            $table->string('posting_period_year')->nullable();
            $table->string('posting_period_month')->nullable();
            $table->integer('months')->nullable();
            $table->decimal('old_gross', 15, 2)->default(0);
            $table->decimal('new_gross', 15, 2)->default(0);
            $table->decimal('total_arrear', 15, 2)->default(0);
            $table->decimal('old_basic', 15, 2)->default(0);
            $table->decimal('new_basic', 15, 2)->default(0);
            $table->decimal('old_pf_wage', 15, 2)->default(0);
            $table->decimal('new_pf_wage', 15, 2)->default(0);
            $table->decimal('pf_diff_emp', 15, 2)->default(0);
            $table->decimal('pf_diff_er', 15, 2)->default(0);
            $table->decimal('eps_diff', 15, 2)->default(0);
            $table->decimal('esi_diff_emp', 15, 2)->default(0);
            $table->decimal('esi_diff_er', 15, 2)->default(0);
            $table->decimal('tds_arrear', 15, 2)->default(0);
            $table->string('sec89_relief_form10e')->nullable();
            $table->string('sanction_ref')->nullable();
            $table->unsignedBigInteger('approver_emp_id')->nullable()->index(); // FK -> employees
            $table->string('approver_name')->nullable();
            $table->string('status')->nullable();
            $table->string('source')->nullable();
            $table->string('posting_run_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrears');
    }
};
