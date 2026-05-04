<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: banks
 *
 * Auto-generated from CSV schema · 29 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->bigIncrements('bank_id');
            $table->string('bank_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('ifsc_master')->nullable();
            $table->string('branch_name')->nullable();
            $table->text('branch_address')->nullable();
            $table->string('branch_state')->nullable();
            $table->string('branch_city')->nullable();
            $table->string('branch_pin')->nullable();
            $table->string('micr_code')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('account_holder_company_id')->nullable();
            $table->string('salary_account_no')->nullable();
            $table->string('statutory_account_no')->nullable();
            $table->string('disbursement_account_no')->nullable();
            $table->string('bank_format_neft')->nullable();
            $table->string('bank_format_rtgs')->nullable();
            $table->string('bank_format_imps')->nullable();
            $table->string('cms_token')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->string('sftp_endpoint')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->boolean('default_bank_flag')->default(false);
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->integer('employees_using')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
