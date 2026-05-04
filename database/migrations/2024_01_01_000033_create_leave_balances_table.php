<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: leave_balances
 *
 * Auto-generated from CSV schema · 17 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->bigIncrements('balance_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->integer('fy')->nullable();
            $table->unsignedBigInteger('leave_type_id')->nullable()->index(); // FK -> leave_types
            $table->string('leave_code')->nullable();
            $table->string('opening_balance')->nullable();
            $table->string('accrued_ytd')->nullable();
            $table->string('availed_ytd')->nullable();
            $table->string('encashed_ytd')->nullable();
            $table->string('lapsed_ytd')->nullable();
            $table->string('closing_balance')->nullable();
            $table->date('last_applied_date')->nullable();
            $table->date('last_accrual_date')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
