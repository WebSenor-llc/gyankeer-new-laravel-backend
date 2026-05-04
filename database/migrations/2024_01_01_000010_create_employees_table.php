<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: employees
 *
 * Auto-generated from CSV schema · 207 columns
 *
 * NOTE: Column lengths have been right-sized to keep the row under MySQL's
 * 65,535-byte row limit (utf8mb4 = 4 bytes/char). InnoDB ROW_FORMAT=DYNAMIC
 * is also enforced so any genuinely long values (TEXT) are stored off-row.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            // Force DYNAMIC row format so TEXT/BLOB columns store off-row.
            $table->engine = 'InnoDB';

            $table->bigIncrements('emp_id');
            $table->uuid('emp_uuid')->nullable();
            $table->string('employee_code', 50)->nullable();
            $table->string('third_party_code', 50)->nullable();
            $table->decimal('old_emp_id_legacy', 15, 2)->default(0);
            $table->string('employee_type', 50)->nullable();
            $table->string('role', 50)->nullable();
            $table->string('employment_status', 30)->nullable();
            $table->string('contract_type', 30)->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('salary_group_id')->nullable()->index(); // FK -> salary_groups
            $table->string('name_prefix', 10)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('full_name', 200)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('religion_optional', 50)->nullable();
            $table->string('category_general_sc_st_obc', 20)->nullable();
            $table->boolean('pwd_status')->default(false);
            $table->boolean('pwd_certificate_no')->default(false);
            $table->string('relation_prefix', 10)->nullable();
            $table->string('relative_name', 150)->nullable();
            $table->string('mothers_name', 150)->nullable();
            $table->string('fathers_name', 150)->nullable();
            $table->string('spouse_name', 150)->nullable();
            $table->integer('no_of_children')->nullable();
            $table->date('dob')->nullable();
            $table->string('dob_proof_doc_id', 100)->nullable();
            $table->string('place_of_birth', 100)->nullable();
            $table->integer('age_years')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->string('signature_path', 255)->nullable();
            $table->string('work_place', 100)->nullable();
            $table->unsignedBigInteger('location_id')->nullable()->index(); // FK -> locations
            $table->unsignedBigInteger('dept_id')->nullable()->index(); // FK -> departments
            $table->unsignedBigInteger('designation_id')->nullable()->index(); // FK -> designations
            $table->string('grade_category', 30)->nullable();
            $table->text('job_description')->nullable();
            $table->unsignedBigInteger('reports_to_emp_id')->nullable()->index(); // FK -> employees
            $table->unsignedBigInteger('dotted_line_emp_id')->nullable()->index(); // FK -> employees
            $table->string('cost_center', 50)->nullable();
            $table->string('business_unit', 50)->nullable();
            $table->date('date_of_joining')->nullable();
            $table->integer('total_career_experience_months')->nullable();
            $table->integer('prev_experience_months')->nullable();
            $table->boolean('on_probation')->default(false);
            $table->integer('probation_period_months')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->decimal('date_of_confirmation', 15, 2)->default(0);
            $table->boolean('confirmed_flag')->default(false);
            $table->boolean('service_discontinue')->default(false);
            $table->decimal('date_of_retirement', 15, 2)->default(0);
            $table->decimal('date_of_leaving', 15, 2)->default(0);
            $table->string('exit_reason', 100)->nullable();
            $table->string('exit_type', 50)->nullable();
            $table->decimal('notice_period_days', 15, 2)->default(0);
            $table->decimal('last_working_day', 15, 2)->default(0);
            $table->boolean('rehire_eligible')->default(false);
            $table->string('company_mobile', 20)->nullable();
            $table->string('personal_mobile', 20)->nullable();
            $table->string('company_email', 150)->nullable();
            $table->string('personal_email', 150)->nullable();
            $table->string('alternate_phone', 20)->nullable();
            $table->text('mailing_address_line1')->nullable();
            $table->text('mailing_address_line2')->nullable();
            $table->string('mailing_city', 80)->nullable();
            $table->string('mailing_state', 80)->nullable();
            $table->string('mailing_pincode', 15)->nullable();
            $table->string('mailing_country', 80)->nullable();
            $table->text('permanent_address_line1')->nullable();
            $table->text('permanent_address_line2')->nullable();
            $table->string('permanent_city', 80)->nullable();
            $table->string('permanent_state', 80)->nullable();
            $table->string('permanent_pincode', 15)->nullable();
            $table->string('permanent_country', 80)->nullable();
            $table->boolean('same_as_mailing')->default(false);
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->text('emergency_contact_address')->nullable();
            $table->string('aadhar_id_no', 12)->nullable();
            $table->string('aadhar_enrollment_no', 30)->nullable();
            $table->boolean('aadhar_pan_linked')->default(false);
            $table->string('pan_no', 10)->nullable();
            $table->string('voter_id_no', 20)->nullable();
            $table->string('driving_license_no', 30)->nullable();
            $table->string('driving_license_expiry', 30)->nullable();
            $table->string('passport_no', 20)->nullable();
            $table->string('passport_expiry', 30)->nullable();
            $table->string('reference_letter_no', 50)->nullable();
            $table->string('offer_letter_no', 50)->nullable();
            $table->string('appointment_letter_no', 50)->nullable();
            $table->boolean('thumb_applicable')->default(false);
            $table->string('biometric_template_id', 100)->nullable();
            $table->string('face_template_id', 100)->nullable();
            $table->string('uan', 20)->nullable();
            $table->string('epf_member_id', 30)->nullable();
            $table->date('epf_join_date')->nullable();
            $table->date('eps_join_date')->nullable();
            $table->decimal('pf_wage_capped', 15, 2)->default(0);
            $table->decimal('vpf_amount', 15, 2)->default(0);
            $table->boolean('international_worker')->default(false);
            $table->boolean('hc_disabled')->default(false);
            $table->date('epf_form11_submitted_on')->nullable();
            $table->string('esi_ip_no', 30)->nullable();
            $table->date('esi_join_date')->nullable();
            $table->decimal('esi_dispensary', 15, 2)->default(0);
            $table->decimal('esi_local_office', 15, 2)->default(0);
            $table->integer('esi_family_count')->nullable();
            $table->boolean('pehchan_card_issued')->default(false);
            $table->date('esi_form1_submitted_on')->nullable();
            $table->string('pt_state', 50)->nullable();
            $table->string('pt_slab_applied', 50)->nullable();
            $table->decimal('pt_monthly_amount', 15, 2)->default(0);
            $table->string('lwf_state', 50)->nullable();
            $table->boolean('lwf_applicable')->default(false);
            $table->decimal('tax_regime', 15, 2)->default(0);
            $table->string('tax_resident_status', 30)->nullable();
            $table->boolean('senior_citizen_flag')->default(false);
            $table->boolean('super_senior_citizen_flag')->default(false);
            $table->decimal('std_deduction_amount', 15, 2)->default(0);
            $table->string('sec_80c_declared', 50)->nullable();
            $table->string('sec_80d_declared', 50)->nullable();
            $table->string('sec_80ccd1b_declared', 50)->nullable();
            $table->string('sec_80e_declared', 50)->nullable();
            $table->string('sec_80g_declared', 50)->nullable();
            $table->string('sec_24b_declared', 50)->nullable();
            $table->decimal('hra_rent_paid_annual', 15, 2)->default(0);
            $table->string('lta_claimed_annual', 50)->nullable();
            $table->boolean('bonus_eligible')->default(false);
            $table->decimal('bonus_wage_cap_7k', 15, 2)->default(0);
            $table->boolean('gratuity_eligible')->default(false);
            $table->decimal('gratuity_provision_amount', 15, 2)->default(0);
            $table->boolean('gratuity_nominee_filed')->default(false);
            $table->date('gratuity_form_f_filed_on')->nullable();
            $table->boolean('edli_nominee_filed')->default(false);
            $table->decimal('current_gross', 15, 2)->default(0);
            $table->decimal('current_basic', 15, 2)->default(0);
            $table->decimal('current_hra', 15, 2)->default(0);
            $table->decimal('current_da', 15, 2)->default(0);
            $table->string('current_conv', 50)->nullable();
            $table->string('current_med', 50)->nullable();
            $table->decimal('current_spl', 15, 2)->default(0);
            $table->string('current_lta', 50)->nullable();
            $table->string('current_fbp', 50)->nullable();
            $table->decimal('current_ctc', 15, 2)->default(0);
            $table->date('last_increment_date')->nullable();
            $table->decimal('last_increment_pct', 8, 2)->nullable();
            $table->decimal('last_increment_old_gross', 15, 2)->default(0);
            $table->decimal('last_increment_new_gross', 15, 2)->default(0);
            $table->unsignedBigInteger('bank_id')->nullable()->index(); // FK -> banks
            $table->string('bank_account_no', 30)->nullable();
            $table->string('bank_ifsc', 11)->nullable();
            $table->string('bank_branch', 100)->nullable();
            $table->string('bank_micr', 9)->nullable();
            $table->string('account_type', 20)->nullable();
            $table->string('account_holder_name', 150)->nullable();
            $table->string('salary_disbursement_mode', 30)->nullable();
            $table->string('upi_vpa', 100)->nullable();
            $table->unsignedBigInteger('secondary_bank_id')->nullable()->index(); // FK -> banks
            $table->string('secondary_account_no', 30)->nullable();
            $table->decimal('secondary_share_pct', 8, 2)->nullable();
            $table->boolean('penny_drop_verified')->default(false);
            $table->date('penny_drop_date')->nullable();
            $table->string('work_visa_type', 50)->nullable();
            $table->string('work_visa_no', 50)->nullable();
            $table->string('work_visa_expiry', 30)->nullable();
            $table->string('work_permit_no', 50)->nullable();
            $table->string('foreign_resident_country', 80)->nullable();
            $table->string('education_highest_qualification', 100)->nullable();
            $table->string('education_institute_top', 200)->nullable();
            $table->integer('years_of_education')->nullable();
            $table->string('bgv_status', 30)->nullable();
            $table->date('bgv_completed_date')->nullable();
            $table->string('bgv_vendor', 100)->nullable();
            $table->decimal('bgv_score', 8, 2)->nullable();
            $table->string('posh_training_completed', 30)->nullable();
            $table->date('posh_training_date')->nullable();
            $table->string('posh_training_expiry', 30)->nullable();
            $table->string('fire_safety_training_completed', 30)->nullable();
            $table->date('code_of_conduct_signed_on')->nullable();
            $table->date('nda_signed_on')->nullable();
            $table->string('mediclaim_policy_no', 50)->nullable();
            $table->string('gpa_policy_no', 50)->nullable();
            $table->string('gli_policy_no', 50)->nullable();
            $table->decimal('sum_insured_mediclaim', 15, 2)->default(0);
            $table->decimal('sum_insured_edli', 15, 2)->default(0);
            $table->unsignedBigInteger('shift_id')->nullable()->index(); // FK -> shifts
            $table->string('weekly_off_pattern', 30)->nullable();
            $table->string('holiday_calendar_id', 50)->nullable();
            $table->boolean('attendance_required')->default(false);
            $table->boolean('gps_clock_in_allowed')->default(false);
            $table->string('performance_last_rating', 20)->nullable();
            $table->string('performance_last_cycle', 30)->nullable();
            $table->boolean('increment_eligible')->default(false);
            $table->boolean('high_potential_flag')->default(false);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->decimal('data_retention_until', 15, 2)->default(0);
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Belt-and-suspenders: enforce DYNAMIC row format so any future TEXT
        // columns are stored off-row and never re-trigger the row-size error.
        DB::statement('ALTER TABLE `employees` ROW_FORMAT=DYNAMIC');
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
