<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Truncates all payroll OUTPUT tables (payslips, salary_runs, statutory
 * records, etc.) — leaves employee master data, attendance, leaves, and
 * configuration intact.
 *
 *   php artisan payroll:reset            # interactive confirm
 *   php artisan payroll:reset --force    # skip confirmation
 */
class ResetPayrollData extends Command
{
    protected $signature   = 'payroll:reset {--force : Skip the confirmation prompt}';
    protected $description = 'Wipe all generated payroll data (payslips, runs, statutory records, GL transactions). Master data and configuration are preserved.';

    /**
     * Tables to TRUNCATE — these are payroll OUTPUT only.
     * Order does not matter inside the FK-disabled block.
     */
    private array $payrollOutputTables = [
        'payslips',
        'salary_runs',
        'salary_transactions',
        'pf_ecr_records',
        'esi_records',
        'pt_records',
        'lwf_records',
        'tds_records',
        'form24q_records',
        'bonus_provisions',
        'gratuity_register',
    ];

    public function handle(): int
    {
        $this->warn('This will permanently delete ALL generated payroll data:');
        foreach ($this->payrollOutputTables as $t) {
            if (Schema::hasTable($t)) {
                $count = DB::table($t)->count();
                $this->line("   - {$t}: {$count} rows");
            }
        }
        $this->newLine();
        $this->info('Master data PRESERVED: employees, companies, departments, designations, salary_groups, banks, salary_components, shifts, holidays, leave_types, attendance_daily, leave_balances, leave_applications, employee_career_events, users_roles, statutory_slabs.');
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Proceed with wipe?', false)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->payrollOutputTables as $t) {
            if (Schema::hasTable($t)) {
                DB::table($t)->truncate();
                $this->line("✓ Truncated {$t}");
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->newLine();
        $this->info('Done. Employee master data and configuration are intact.');
        $this->info('Next: import new data, then run payroll engine for the desired period.');

        return self::SUCCESS;
    }
}
