<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: employee_documents
 *
 * Auto-generated from CSV schema · 23 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->bigIncrements('doc_id');
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('doc_category')->nullable();
            $table->string('doc_type')->nullable();
            $table->string('doc_name')->nullable();
            $table->string('doc_path')->nullable();
            $table->integer('file_size_kb')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('requirement')->nullable();
            $table->string('verification_status')->nullable();
            $table->string('verified_by')->nullable();
            $table->date('verified_date')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->date('uploaded_on')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('version')->nullable();
            $table->string('hash_sha256')->nullable();
            $table->string('retention_until')->nullable();
            $table->string('encryption_status')->nullable();
            $table->integer('access_count')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
