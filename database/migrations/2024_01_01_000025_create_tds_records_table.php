<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: tds_records
 *
 * Auto-generated from CSV schema · 31 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tds_records', function (Blueprint $table) {
            $table->bigIncrements('tds_id');
            $table->integer('period_year')->nullable();
            $table->integer('period_month')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->string('pan')->nullable();
            $table->string('regime')->nullable();
            $table->decimal('annual_gross', 15, 2)->default(0);
            $table->string('std_deduction')->nullable();
            $table->string('sec_80c')->nullable();
            $table->string('sec_80d')->nullable();
            $table->string('sec_80ccd1b')->nullable();
            $table->decimal('hra_exempt', 15, 2)->default(0);
            $table->string('lta_exempt')->nullable();
            $table->string('other_exempt')->nullable();
            $table->decimal('taxable_income', 15, 2)->default(0);
            $table->decimal('tax_slab_breakup', 15, 2)->default(0);
            $table->decimal('tax_before_cess', 15, 2)->default(0);
            $table->decimal('cess_4pct', 15, 2)->default(0);
            $table->decimal('total_annual_tax', 15, 2)->default(0);
            $table->decimal('monthly_tds_proration', 15, 2)->default(0);
            $table->decimal('monthly_tds_for_period', 15, 2)->default(0);
            $table->decimal('ytd_tds_paid', 15, 2)->default(0);
            $table->string('ytd_balance_due')->nullable();
            $table->string('form24q_quarter')->nullable();
            $table->date('form24q_filed_date')->nullable();
            $table->string('traces_token')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tds_records');
    }
};
