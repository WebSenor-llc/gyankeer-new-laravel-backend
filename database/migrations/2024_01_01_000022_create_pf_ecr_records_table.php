<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: pf_ecr_records
 *
 * Auto-generated from CSV schema · 27 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('pf_ecr_records', function (Blueprint $table) {
            $table->bigIncrements('ecr_id');
            $table->integer('period_year')->nullable();
            $table->integer('period_month')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // FK -> companies
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('uan')->nullable();
            $table->string('member_name')->nullable();
            $table->decimal('member_id_pf', 15, 2)->default(0);
            $table->decimal('gross_wage', 15, 2)->default(0);
            $table->decimal('epf_wage_capped', 15, 2)->default(0);
            $table->decimal('eps_wage_capped', 15, 2)->default(0);
            $table->decimal('edli_wage_capped', 15, 2)->default(0);
            $table->decimal('ee_share_12pct', 15, 2)->default(0);
            $table->decimal('eps_8_33', 15, 2)->default(0);
            $table->decimal('er_share_3_67', 15, 2)->default(0);
            $table->decimal('edli_0_5', 15, 2)->default(0);
            $table->decimal('pf_admin_0_5', 15, 2)->default(0);
            $table->decimal('ncp_days', 15, 2)->default(0);
            $table->string('refund_member')->nullable();
            $table->decimal('lop_amount', 15, 2)->default(0);
            $table->string('challan_no')->nullable();
            $table->date('challan_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('tracking_id')->nullable();
            $table->string('filed_status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pf_ecr_records');
    }
};
