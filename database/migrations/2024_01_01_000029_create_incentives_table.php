<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: incentives
 *
 * Auto-generated from CSV schema · 30 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('incentives', function (Blueprint $table) {
            $table->bigIncrements('inc_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->integer('period_year')->nullable();
            $table->integer('period_month')->nullable();
            $table->string('type')->nullable();
            $table->string('sub_type')->nullable();
            $table->string('linked_kpi')->nullable();
            $table->text('reason')->nullable();
            $table->string('slab')->nullable();
            $table->string('target')->nullable();
            $table->string('achieved')->nullable();
            $table->decimal('achievement_pct', 8, 2)->nullable();
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('multiplier', 8, 2)->nullable();
            $table->decimal('final_amount', 15, 2)->default(0);
            $table->boolean('taxable')->default(false);
            $table->boolean('pf_wage_flag')->default(false);
            $table->boolean('esi_wage_flag')->default(false);
            $table->unsignedBigInteger('approver_emp_id')->nullable()->index(); // FK -> employees
            $table->string('approver_name')->nullable();
            $table->date('approval_date')->nullable();
            $table->string('posting_run_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('sanction_doc')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentives');
    }
};
