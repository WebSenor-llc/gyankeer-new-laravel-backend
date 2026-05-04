<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: employee_career_events
 *
 * Auto-generated from CSV schema · 16 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_career_events', function (Blueprint $table) {
            $table->bigIncrements('event_id');
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->date('event_date')->nullable();
            $table->string('event_type')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->decimal('salary_old', 15, 2)->default(0);
            $table->decimal('salary_new', 15, 2)->default(0);
            $table->decimal('hike_percent', 8, 2)->nullable();
            $table->string('performance_rating')->nullable();
            $table->unsignedBigInteger('approver_emp_id')->nullable()->index(); // FK -> employees
            $table->string('ref_letter_no')->nullable();
            $table->string('source_doc_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_career_events');
    }
};
