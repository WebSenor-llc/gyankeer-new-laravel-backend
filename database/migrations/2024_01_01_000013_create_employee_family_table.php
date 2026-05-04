<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: employee_family
 *
 * Auto-generated from CSV schema · 19 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_family', function (Blueprint $table) {
            $table->bigIncrements('family_id');
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('member_name')->nullable();
            $table->string('relation')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('aadhaar_no_masked')->nullable();
            $table->boolean('dependent_flag')->default(false);
            $table->text('address')->nullable();
            $table->boolean('address_same_as_employee')->default(false);
            $table->text('documents_provided')->nullable();
            $table->boolean('esi_eligible')->default(false);
            $table->boolean('mediclaim_covered')->default(false);
            $table->string('contact_phone')->nullable();
            $table->string('occupation')->nullable();
            $table->string('annual_income')->nullable();
            $table->boolean('minor_flag')->default(false);
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_family');
    }
};
