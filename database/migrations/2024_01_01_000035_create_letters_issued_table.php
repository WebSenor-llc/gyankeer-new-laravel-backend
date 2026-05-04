<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: letters_issued
 *
 * Auto-generated from CSV schema · 24 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('letters_issued', function (Blueprint $table) {
            $table->bigIncrements('letter_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->string('letter_type')->nullable();
            $table->string('template_code')->nullable();
            $table->string('letter_no')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->unsignedBigInteger('signed_by_emp_id')->nullable()->index(); // FK -> employees
            $table->string('signed_by_name')->nullable();
            $table->string('signed_by_designation')->nullable();
            $table->string('dsc_serial')->nullable();
            $table->timestamp('dsc_signed_at')->nullable();
            $table->string('delivery_mode')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('delivery_email')->nullable();
            $table->string('delivery_whatsapp')->nullable();
            $table->string('doc_path')->nullable();
            $table->integer('file_size_kb')->nullable();
            $table->string('status')->nullable();
            $table->integer('version')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letters_issued');
    }
};
