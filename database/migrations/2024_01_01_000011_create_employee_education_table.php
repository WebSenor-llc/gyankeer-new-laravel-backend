<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: employee_education
 *
 * Auto-generated from CSV schema · 22 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_education', function (Blueprint $table) {
            $table->bigIncrements('edu_id');
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->integer('sequence_no')->nullable();
            $table->string('qualification')->nullable();
            $table->decimal('specialization', 15, 2)->default(0);
            $table->string('institute')->nullable();
            $table->string('board_university')->nullable();
            $table->string('year_of_passing')->nullable();
            $table->string('mode')->nullable();
            $table->string('marks_type')->nullable();
            $table->integer('marks_obtained')->nullable();
            $table->integer('total_marks')->nullable();
            $table->decimal('percentage_or_cgpa', 8, 2)->nullable();
            $table->string('division_class')->nullable();
            $table->string('medium')->nullable();
            $table->string('verification_status')->nullable();
            $table->string('verified_by')->nullable();
            $table->date('verified_date')->nullable();
            $table->string('document_id')->nullable();
            $table->string('doc_ref')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_education');
    }
};
