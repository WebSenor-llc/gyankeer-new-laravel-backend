<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: salary_components
 *
 * Auto-generated from CSV schema · 27 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->bigIncrements('component_id');
            $table->string('component_code')->nullable();
            $table->string('component_name')->nullable();
            $table->string('component_type')->nullable();
            $table->string('calculation_type')->nullable();
            $table->text('formula')->nullable();
            $table->decimal('percentage_base', 8, 2)->nullable();
            $table->decimal('fixed_amount', 15, 2)->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('taxable_under_old')->default(false);
            $table->boolean('taxable_under_new')->default(false);
            $table->string('exemption_section')->nullable();
            $table->boolean('partial_exemption_rule')->default(false);
            $table->boolean('pf_wage')->default(false);
            $table->boolean('esi_wage')->default(false);
            $table->boolean('pt_wage')->default(false);
            $table->boolean('gratuity_wage')->default(false);
            $table->boolean('bonus_wage')->default(false);
            $table->boolean('show_on_payslip')->default(false);
            $table->boolean('statutory_flag')->default(false);
            $table->string('gl_account_code')->nullable();
            $table->integer('sequence_order')->nullable();
            $table->string('applicable_employee_types')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
