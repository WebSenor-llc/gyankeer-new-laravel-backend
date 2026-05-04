<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds VPF (Voluntary Provident Fund) support:
 *   - manual_deductions.vpf_deduction → user-entered VPF amount per (emp × month)
 *   - payslips.vpf                    → applied VPF column on the payslip
 *
 * VPF is an EXTRA employee contribution to the EPF account, on top of the
 * mandatory 12% EPF. It is fully deducted from net pay but unlike EPF it has
 * no statutory cap (employees can contribute up to 100% of Basic+DA).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('manual_deductions') && !Schema::hasColumn('manual_deductions', 'vpf_deduction')) {
            Schema::table('manual_deductions', function (Blueprint $table) {
                $table->decimal('vpf_deduction', 15, 2)->default(0)->after('tds_deduction');
            });
        }

        if (Schema::hasTable('payslips') && !Schema::hasColumn('payslips', 'vpf')) {
            Schema::table('payslips', function (Blueprint $table) {
                $table->decimal('vpf', 15, 2)->default(0)->after('epf_emp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('manual_deductions', 'vpf_deduction')) {
            Schema::table('manual_deductions', function (Blueprint $table) {
                $table->dropColumn('vpf_deduction');
            });
        }
        if (Schema::hasColumn('payslips', 'vpf')) {
            Schema::table('payslips', function (Blueprint $table) {
                $table->dropColumn('vpf');
            });
        }
    }
};
