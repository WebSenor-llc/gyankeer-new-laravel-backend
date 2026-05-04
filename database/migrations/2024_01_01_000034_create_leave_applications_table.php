<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: leave_applications
 *
 * Auto-generated from CSV schema · 24 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->bigIncrements('leave_app_id');
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('employee_name')->nullable();
            $table->unsignedBigInteger('leave_type_id')->nullable()->index(); // FK -> leave_types
            $table->string('leave_code')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->integer('days')->nullable();
            $table->boolean('half_day_flag')->default(false);
            $table->string('half_day_type')->nullable();
            $table->text('reason')->nullable();
            $table->decimal('medical_cert_attached', 15, 2)->default(0);
            $table->text('attachment_path')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('approver_emp_id')->nullable()->index(); // FK -> employees
            $table->string('approver_name')->nullable();
            $table->string('approval_status')->nullable();
            $table->date('approval_date')->nullable();
            $table->string('approval_remarks')->nullable();
            $table->string('balance_after')->nullable();
            $table->string('contact_during_leave')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
