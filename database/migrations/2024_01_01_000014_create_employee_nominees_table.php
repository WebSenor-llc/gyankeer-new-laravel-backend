<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: employee_nominees
 *
 * Auto-generated from CSV schema · 18 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_nominees', function (Blueprint $table) {
            $table->bigIncrements('nominee_id');
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('nominee_for')->nullable();
            $table->string('nominee_name')->nullable();
            $table->string('relation')->nullable();
            $table->date('dob')->nullable();
            $table->string('aadhaar_no_masked')->nullable();
            $table->decimal('share_percent', 8, 2)->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('form_filed')->nullable();
            $table->date('form_filed_date')->nullable();
            $table->string('filed_at_office')->nullable();
            $table->string('status')->nullable();
            $table->string('witness_name')->nullable();
            $table->string('witness_signature_path')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_nominees');
    }
};
