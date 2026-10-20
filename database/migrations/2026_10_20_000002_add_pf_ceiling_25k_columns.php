<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EPFO 2026 ceiling bump — supporting columns.
 *
 * payslips.pf_wage_cap_snapshot      immutable per-slip cap for audit
 * salary_runs.pf_admin_topup         company admin-floor top-up (₹500/mo)
 * salary_runs.pf_inspection_epf      exempted-trust EPF inspection (0.18% / ₹8750 floor)
 * salary_runs.pf_inspection_edli     exempted-trust EDLI inspection (0.005% / ₹1250 floor)
 * companies.exempted_trust_flag      activates inspection-charge computation
 * employees.iw_flag                  International Worker — no PF wage cap
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payslips') && !Schema::hasColumn('payslips', 'pf_wage_cap_snapshot')) {
            Schema::table('payslips', function (Blueprint $t) {
                $t->decimal('pf_wage_cap_snapshot', 10, 2)->nullable()->after('pf_admin');
            });
        }

        if (Schema::hasTable('salary_runs')) {
            Schema::table('salary_runs', function (Blueprint $t) {
                if (!Schema::hasColumn('salary_runs', 'pf_admin_topup')) {
                    $t->decimal('pf_admin_topup', 12, 2)->default(0)->after('total_admin');
                }
                if (!Schema::hasColumn('salary_runs', 'pf_inspection_epf')) {
                    $t->decimal('pf_inspection_epf', 12, 2)->default(0)->after('pf_admin_topup');
                }
                if (!Schema::hasColumn('salary_runs', 'pf_inspection_edli')) {
                    $t->decimal('pf_inspection_edli', 12, 2)->default(0)->after('pf_inspection_epf');
                }
            });
        }

        if (Schema::hasTable('companies') && !Schema::hasColumn('companies', 'exempted_trust_flag')) {
            Schema::table('companies', function (Blueprint $t) {
                $t->boolean('exempted_trust_flag')->default(false);
            });
        }

        // NOTE: employees.iw_flag deliberately NOT added here — the employees
        // table has hit MySQL's 8126-byte row-size limit (too many legacy
        // varchar columns). PayrollEngine::computeForEmployee() falls back to
        // iw_flag=false whenever the column is absent, so International Worker
        // support stays dormant until a dedicated sidecar table is introduced.
    }

    public function down(): void
    {
        if (Schema::hasColumn('payslips', 'pf_wage_cap_snapshot')) {
            Schema::table('payslips', fn (Blueprint $t) => $t->dropColumn('pf_wage_cap_snapshot'));
        }
        if (Schema::hasTable('salary_runs')) {
            Schema::table('salary_runs', function (Blueprint $t) {
                foreach (['pf_admin_topup', 'pf_inspection_epf', 'pf_inspection_edli'] as $c) {
                    if (Schema::hasColumn('salary_runs', $c)) $t->dropColumn($c);
                }
            });
        }
        if (Schema::hasColumn('companies', 'exempted_trust_flag')) {
            Schema::table('companies', fn (Blueprint $t) => $t->dropColumn('exempted_trust_flag'));
        }
        if (Schema::hasColumn('employees', 'iw_flag')) {
            Schema::table('employees', fn (Blueprint $t) => $t->dropColumn('iw_flag'));
        }
    }
};
