<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: companies
 *
 * Auto-generated from CSV schema · 63 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->bigIncrements('company_id');
            $table->string('company_code')->nullable();
            $table->string('company_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('status')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->string('cin')->nullable();
            $table->string('pan')->nullable();
            $table->string('tan')->nullable();
            $table->string('gstin')->nullable();
            $table->string('lei')->nullable();
            $table->string('msme_udyam_no')->nullable();
            $table->string('iec_code')->nullable();
            $table->string('epf_establishment_code')->nullable();
            $table->decimal('epf_office', 15, 2)->default(0);
            $table->string('esic_code')->nullable();
            $table->decimal('esic_office', 15, 2)->default(0);
            $table->string('factory_license_no')->nullable();
            $table->string('contract_labour_lic_no')->nullable();
            $table->string('shops_estab_reg_no')->nullable();
            $table->string('shops_estab_state')->nullable();
            $table->string('pt_state_reg_no')->nullable();
            $table->string('lwf_state')->nullable();
            $table->string('psara_license_no')->nullable();
            $table->string('iso_certifications')->nullable();
            $table->string('industry_code_nic')->nullable();
            $table->string('sector')->nullable();
            $table->integer('business_unit_count')->nullable();
            $table->string('authorized_signatory_name')->nullable();
            $table->string('authorized_signatory_designation')->nullable();
            $table->string('authorized_signatory_pan')->nullable();
            $table->string('dsc_serial')->nullable();
            $table->string('dsc_expiry')->nullable();
            $table->date('incorporation_date')->nullable();
            $table->integer('fy_start_month')->nullable();
            $table->string('base_currency')->nullable();
            $table->string('reporting_currency')->nullable();
            $table->text('registered_address_line1')->nullable();
            $table->text('registered_address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pin_code')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('primary_contact')->nullable();
            $table->string('hr_contact_email')->nullable();
            $table->unsignedBigInteger('parent_company_id')->nullable()->index(); // FK -> companies
            $table->boolean('group_consolidation_flag')->default(false);
            $table->string('default_payroll_bank_id')->nullable();
            $table->string('salary_account_no')->nullable();
            $table->string('payroll_cycle')->nullable();
            $table->decimal('payroll_cutoff_day', 15, 2)->default(0);
            $table->decimal('payroll_pay_day', 15, 2)->default(0);
            $table->string('holiday_calendar_id')->nullable();
            $table->string('default_pt_state')->nullable();
            $table->string('default_lwf_state')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
