<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\SalaryGroup;
use Illuminate\Console\Command;

/**
 * Hard-delete payslips for a specific salary group × period.
 *
 *   php artisan payroll:wipe-group "GTPPL- Staff" --year=2026 --month=4
 *   php artisan payroll:wipe-group 9             --year=2026 --month=4
 */
class WipeGroupPayroll extends Command
{
    protected $signature   = 'payroll:wipe-group {group : Salary group name (or ID)} {--year= : Period year} {--month= : Period month (1-12)}';
    protected $description = 'Hard-delete generated payslips for a specific salary group × period. Attendance, manual deductions, and OT entries are preserved.';

    public function handle(): int
    {
        $g = is_numeric($this->argument('group'))
            ? SalaryGroup::find((int) $this->argument('group'))
            : SalaryGroup::whereRaw('LOWER(TRIM(salary_group_name)) = ?', [strtolower(trim($this->argument('group')))])->first();

        if (!$g) {
            $this->error('Salary group not found: ' . $this->argument('group'));
            return self::FAILURE;
        }

        $year  = (int) ($this->option('year')  ?? now()->year);
        $month = (int) ($this->option('month') ?? now()->month);

        $this->info("Group [{$g->salary_group_id}] {$g->salary_group_name}");

        $empIds = Employee::where('salary_group_id', $g->salary_group_id)->pluck('emp_id');
        $this->line("Employees in group: " . $empIds->count());

        $cnt = Payslip::withTrashed()
            ->whereIn('emp_id', $empIds)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->count();

        if ($cnt === 0) {
            $this->info("No payslips to delete for {$g->salary_group_name} in {$month}/{$year}.");
            return self::SUCCESS;
        }

        if (!$this->confirm("Hard-delete {$cnt} payslip(s) for {$g->salary_group_name} ({$month}/{$year})?", true)) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        Payslip::withTrashed()
            ->whereIn('emp_id', $empIds)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->forceDelete();

        $this->info("✓ Hard-deleted {$cnt} payslip(s). Attendance, manual_deductions, and overtime preserved.");
        $this->newLine();
        $this->line('Next: regenerate at /payroll/generate?salary_group_id=' . $g->salary_group_id . '&year=' . $year . '&month=' . $month . '&get_list=1');
        return self::SUCCESS;
    }
}
