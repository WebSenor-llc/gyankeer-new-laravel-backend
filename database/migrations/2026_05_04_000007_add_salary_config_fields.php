<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds SUGAM HR-style Employee Salary Configuration fields to the employees table.
 * These mirror the "Manage Salary" form fields shown in the SUGAM screenshot.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $cols = [
                'pf_applicable_flag'      => fn() => $table->boolean('pf_applicable_flag')->default(true),
                'fpf_applicable_flag'     => fn() => $table->boolean('fpf_applicable_flag')->default(true),
                'esi_applicable_flag'     => fn() => $table->boolean('esi_applicable_flag')->default(true),
                'co_applicable_flag'      => fn() => $table->boolean('co_applicable_flag')->default(false),
                'overtime_applicable_flag'=> fn() => $table->boolean('overtime_applicable_flag')->default(false),
                'overtime_rate'           => fn() => $table->decimal('overtime_rate', 4, 2)->default(2.00),
                'lwf_apply_flag'          => fn() => $table->boolean('lwf_apply_flag')->default(false),
                'ltc_entitled_flag'       => fn() => $table->boolean('ltc_entitled_flag')->default(false),
                'group_gratuity_code'     => fn() => $table->string('group_gratuity_code', 50)->nullable(),
                'payment_mode'            => fn() => $table->string('payment_mode', 30)->nullable(),
                'auto_calc_flag'          => fn() => $table->boolean('auto_calc_flag')->default(true),
                'da_pct'                  => fn() => $table->decimal('da_pct', 5, 2)->default(10),
                'hra_pct'                 => fn() => $table->decimal('hra_pct', 5, 2)->default(50),
                'conv_pct'                => fn() => $table->decimal('conv_pct', 5, 2)->default(8),
                'medical_pct'             => fn() => $table->decimal('medical_pct', 5, 2)->default(5),
                'education_pct'           => fn() => $table->decimal('education_pct', 5, 2)->default(5),
                'special_house_rent'      => fn() => $table->decimal('special_house_rent', 15, 2)->default(0),
                'site_allowance'          => fn() => $table->decimal('site_allowance', 15, 2)->default(0),
                'sp_conv_petrol'          => fn() => $table->decimal('sp_conv_petrol', 15, 2)->default(0),
                'other_allowance'         => fn() => $table->decimal('other_allowance', 15, 2)->default(0),
                'deputation_allowance'    => fn() => $table->decimal('deputation_allowance', 15, 2)->default(0),
                'food_allowance'          => fn() => $table->decimal('food_allowance', 15, 2)->default(0),
                'city_allowance'          => fn() => $table->decimal('city_allowance', 15, 2)->default(0),
                'voucher_cash_allow'      => fn() => $table->decimal('voucher_cash_allow', 15, 2)->default(0),
                'kra_amount'              => fn() => $table->decimal('kra_amount', 15, 2)->default(0),
                'hard_duty_allow'         => fn() => $table->decimal('hard_duty_allow', 15, 2)->default(0),
                'education_allow'         => fn() => $table->decimal('education_allow', 15, 2)->default(0),
            ];
            foreach ($cols as $name => $build) {
                if (!Schema::hasColumn('employees', $name)) $build();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $drop = ['pf_applicable_flag','fpf_applicable_flag','esi_applicable_flag','co_applicable_flag',
                'overtime_applicable_flag','overtime_rate','lwf_apply_flag','ltc_entitled_flag',
                'group_gratuity_code','payment_mode','auto_calc_flag',
                'da_pct','hra_pct','conv_pct','medical_pct','education_pct',
                'special_house_rent','site_allowance','sp_conv_petrol','other_allowance',
                'deputation_allowance','food_allowance','city_allowance',
                'voucher_cash_allow','kra_amount','hard_duty_allow','education_allow'];
            foreach ($drop as $col) {
                if (Schema::hasColumn('employees', $col)) $table->dropColumn($col);
            }
        });
    }
};
