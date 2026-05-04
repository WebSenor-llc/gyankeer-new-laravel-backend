<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: salary_groups
 *
 * Auto-generated from CSV schema · 21 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_groups', function (Blueprint $table) {
            $table->bigIncrements('salary_group_id');
            $table->string('salary_group_name')->nullable();
            $table->string('group_type')->nullable();
            $table->decimal('bonus_per', 8, 2)->nullable();
            $table->string('under_company')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->boolean('pf_applicable')->default(false);
            $table->boolean('esi_applicable')->default(false);
            $table->boolean('pt_applicable')->default(false);
            $table->boolean('lwf_applicable')->default(false);
            $table->boolean('gratuity_applicable')->default(false);
            $table->string('min_wage_state')->nullable();
            $table->string('wage_periodicity')->nullable();
            $table->boolean('overtime_eligible')->default(false);
            $table->string('status')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_groups');
    }
};
