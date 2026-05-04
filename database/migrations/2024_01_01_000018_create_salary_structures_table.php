<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: salary_structures
 *
 * Auto-generated from CSV schema · 40 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->bigIncrements('structure_id');
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->unsignedBigInteger('salary_group_id')->nullable()->index(); // FK -> salary_groups
            $table->integer('version')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status')->nullable();
            $table->decimal('basic', 15, 2)->default(0);
            $table->decimal('hra', 15, 2)->default(0);
            $table->decimal('da', 15, 2)->default(0);
            $table->decimal('conveyance', 15, 2)->default(0);
            $table->decimal('medical', 15, 2)->default(0);
            $table->decimal('special_allowance', 15, 2)->default(0);
            $table->string('lta')->nullable();
            $table->string('telephone')->nullable();
            $table->string('meal_card')->nullable();
            $table->string('fuel_reimb')->nullable();
            $table->string('books')->nullable();
            $table->decimal('other_allow_1', 15, 2)->default(0);
            $table->decimal('other_allow_2', 15, 2)->default(0);
            $table->decimal('gross_monthly', 15, 2)->default(0);
            $table->decimal('gross_annual', 15, 2)->default(0);
            $table->decimal('employer_pf', 15, 2)->default(0);
            $table->decimal('eps', 15, 2)->default(0);
            $table->decimal('edli', 15, 2)->default(0);
            $table->decimal('pf_admin', 15, 2)->default(0);
            $table->decimal('employer_esi', 15, 2)->default(0);
            $table->decimal('gratuity_provision', 15, 2)->default(0);
            $table->decimal('lwf_employer', 15, 2)->default(0);
            $table->decimal('ctc_monthly', 15, 2)->default(0);
            $table->decimal('ctc_annual', 15, 2)->default(0);
            $table->boolean('fbp_eligible')->default(false);
            $table->string('fbp_monthly')->nullable();
            $table->decimal('variable_pay_pct', 8, 2)->nullable();
            $table->decimal('retention_bonus_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('approver_emp_id')->nullable()->index(); // FK -> employees
            $table->date('approval_date')->nullable();
            $table->string('restructure_reason')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
