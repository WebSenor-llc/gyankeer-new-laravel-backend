<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: gratuity_register
 *
 * Auto-generated from CSV schema · 24 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('gratuity_register', function (Blueprint $table) {
            $table->bigIncrements('gratuity_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->date('doj')->nullable();
            $table->string('dol')->nullable();
            $table->integer('years_of_service')->nullable();
            $table->boolean('eligible_5yrs')->default(false);
            $table->decimal('last_basic_da_monthly', 15, 2)->default(0);
            $table->string('gratuity_formula')->nullable();
            $table->decimal('provision_amount', 15, 2)->default(0);
            $table->decimal('vested_amount', 15, 2)->default(0);
            $table->string('capped_at_20l')->nullable();
            $table->string('funded_via_lic')->nullable();
            $table->string('lic_policy_no')->nullable();
            $table->decimal('payable_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->decimal('tax_exempt_section', 15, 2)->default(0);
            $table->decimal('tax_exempt_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gratuity_register');
    }
};
