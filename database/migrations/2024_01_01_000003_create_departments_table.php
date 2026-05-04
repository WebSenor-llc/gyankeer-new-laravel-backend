<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: departments
 *
 * Auto-generated from CSV schema · 16 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->bigIncrements('dept_id');
            $table->string('dept_code')->nullable();
            $table->string('dept_name')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('parent_dept_id')->nullable()->index(); // FK -> departments
            $table->unsignedBigInteger('dept_head_emp_id')->nullable()->index(); // FK -> employees
            $table->string('cost_center_code')->nullable();
            $table->string('gl_expense_account')->nullable();
            $table->unsignedBigInteger('location_id')->nullable()->index(); // FK -> locations
            $table->string('business_unit')->nullable();
            $table->integer('budgeted_headcount')->nullable();
            $table->integer('actual_headcount')->nullable();
            $table->decimal('attrition_target_pct', 8, 2)->nullable();
            $table->boolean('active_flag')->default(false);
            $table->date('effective_from')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
