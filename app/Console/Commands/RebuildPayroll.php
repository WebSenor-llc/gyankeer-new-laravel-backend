<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\SalaryRun;
use App\Services\PayrollEngine;
use Database\Seeders\SalaryUpdateV2Seeder;
use Database\Seeders\SetEmployeeStateRJSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 *   php artisan payroll:rebuild --year=2026 --month=4
 *   php artisan payroll:rebuild --year=2026 --month=4 --force
 *
 * Single end-to-end pipeline:
 *   1. Wipe all payroll output tables (payslips, salary_runs, statutory rec.)
 *   2. Re-import / re-apply v2 salary update (FY 2025-26 increment Excel)
 *   3. Ensure all employees have pt_state=RJ, lwf_state=RJ
 *   4. Create a fresh SalaryRun for the requested period
 *   5. Run the PayrollEngine with current CoSS 2020 logic
 */
class RebuildPayroll extends Command
{
    protected $signature = 'payroll:rebuild
                            {--year= : Period year (default current)}
                            {--month= : Period month (default current)}
                            {--company= : Company id (default 1)}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Wipe payroll, re-apply salary updates, and recompute fresh payslips for a period.';

    private array $payrollOutputTables = [
        'payslips','salary_runs','salary_transactions',
        'pf_ecr_records','esi_records','pt_records','lwf_records',
        'tds_records','form24q_records','bonus_provisions','gratuity_register',
    ];

    public function handle(PayrollEngine $engine): int
    {
        $year    = (int) ($this->option('year')    ?: now()->year);
        $month   = (int) ($this->option('month')   ?: now()->month);
        $company = (int) ($this->option('company') ?: 1);

        $this->info("Rebuild payroll for company {$company}, period {$month}/{$year}");
        if (!Company::where('company_id', $company)->exists()) {
            $this->error("Company {$company} not found.");
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('This will wipe all payroll output and recompute. Proceed?', false)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        // ─────────────────────────────────────────────────────────────────
        // Step 1: Wipe payroll output
        // ─────────────────────────────────────────────────────────────────
        $this->newLine();
        $this->info('▶ Step 1/5  Wiping payroll output tables');
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->payrollOutputTables as $t) {
            if (Schema::hasTable($t)) {
                DB::table($t)->truncate();
                $this->line("    truncated {$t}");
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        // ─────────────────────────────────────────────────────────────────
        // Step 2: Re-apply v2 salary updates (idempotent)
        // ─────────────────────────────────────────────────────────────────
        $this->newLine();
        $this->info('▶ Step 2/5  Re-applying FY 2025-26 v2 salary updates');
        try {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\SalaryUpdateV2Seeder',
                '--force' => true,
            ]);
            $this->line('    ' . trim(Artisan::output()));
        } catch (\Throwable $e) {
            $this->warn('    seeder error: ' . $e->getMessage());
        }

        // ─────────────────────────────────────────────────────────────────
        // Step 3: Set RJ state on all employees
        // ─────────────────────────────────────────────────────────────────
        $this->newLine();
        $this->info('▶ Step 3/5  Setting pt_state=RJ, lwf_state=RJ on all employees');
        try {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\SetEmployeeStateRJSeeder',
                '--force' => true,
            ]);
            $this->line('    ' . trim(Artisan::output()));
        } catch (\Throwable $e) {
            $this->warn('    seeder error: ' . $e->getMessage());
        }

        // ─────────────────────────────────────────────────────────────────
        // Step 4: Create salary run
        // ─────────────────────────────────────────────────────────────────
        $this->newLine();
        $this->info("▶ Step 4/5  Creating salary run for {$month}/{$year}");
        $run = SalaryRun::create([
            'run_code'       => sprintf('SALRUN-%04d-%02d', $year, $month),
            'period_year'    => $year,
            'period_month'   => $month,
            'company_id'     => $company,
            'status'         => 'Draft',
            'run_started_at' => now(),
            'created_by'     => 'payroll:rebuild',
        ]);
        $this->line("    created run #{$run->run_id} ({$run->run_code})");

        // ─────────────────────────────────────────────────────────────────
        // Step 5: Run PayrollEngine
        // ─────────────────────────────────────────────────────────────────
        $this->newLine();
        $this->info('▶ Step 5/5  Running PayrollEngine (CoSS 2020 rules)');

        $employees = Employee::where('company_id', $company)
            ->where('active_flag', true)
            ->get();
        $this->line("    {$employees->count()} active employees");

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        $totals = [
            'earnings'=>0,'deductions'=>0,'net'=>0,'ctc'=>0,
            'pf_emp'=>0,'pf_er'=>0,'eps'=>0,'edli'=>0,'admin'=>0,
            'esi_emp'=>0,'esi_er'=>0,'pt'=>0,'lwf_emp'=>0,'lwf_er'=>0,
            'tds'=>0,'bonus'=>0,'gratuity'=>0,
        ];
        $skipped = 0;

        foreach ($employees as $emp) {
            try {
                if (!$emp->current_gross && !$emp->current_basic) {
                    $skipped++; $bar->advance(); continue;
                }
                $p = $engine->computeForEmployee($emp, $run);
                foreach ([
                    'earnings'=>'gross_earnings','deductions'=>'total_deductions',
                    'net'=>'net_pay','ctc'=>'total_employer_cost',
                    'pf_emp'=>'epf_emp','pf_er'=>'employer_pf','eps'=>'eps','edli'=>'edli','admin'=>'pf_admin',
                    'esi_emp'=>'esi_emp','esi_er'=>'employer_esi','pt'=>'pt',
                    'lwf_emp'=>'lwf_emp','lwf_er'=>'lwf_employer','tds'=>'tds',
                    'bonus'=>'bonus','gratuity'=>'gratuity_provision',
                ] as $k => $col) {
                    $totals[$k] += $p->{$col} ?? 0;
                }
            } catch (\Throwable $ex) {
                $skipped++;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $run->update([
            'eligible_emp_count'       => $employees->count() - $skipped,
            'total_earnings'           => $totals['earnings'],
            'total_deductions'         => $totals['deductions'],
            'total_net_payout'         => $totals['net'],
            'total_employer_cost'      => $totals['ctc'],
            'total_pf_emp'             => $totals['pf_emp'],
            'total_pf_er'              => $totals['pf_er'],
            'total_eps'                => $totals['eps'],
            'total_edli'               => $totals['edli'],
            'total_admin'              => $totals['admin'],
            'total_esi_emp'            => $totals['esi_emp'],
            'total_esi_er'             => $totals['esi_er'],
            'total_pt'                 => $totals['pt'],
            'total_lwf_emp'            => $totals['lwf_emp'],
            'total_lwf_er'             => $totals['lwf_er'],
            'total_tds'                => $totals['tds'],
            'total_bonus_provision'    => $totals['bonus'],
            'total_gratuity_provision' => $totals['gratuity'],
            'calc_completed_at'        => now(),
            'status'                   => 'Computed',
        ]);

        $generated = $employees->count() - $skipped;
        $this->newLine();
        $this->info("✓ Rebuild complete. {$generated} payslips generated, {$skipped} skipped.");
        $this->table(['Metric', '₹ Amount'], [
            ['Total Gross',          number_format($totals['earnings'], 2)],
            ['Total Deductions',     number_format($totals['deductions'], 2)],
            ['Total Net Payout',     number_format($totals['net'], 2)],
            ['Employee EPF',         number_format($totals['pf_emp'], 2)],
            ['Employer EPF',         number_format($totals['pf_er'], 2)],
            ['EPS',                  number_format($totals['eps'], 2)],
            ['EDLI',                 number_format($totals['edli'], 2)],
            ['Employee ESI',         number_format($totals['esi_emp'], 2)],
            ['Employer ESI',         number_format($totals['esi_er'], 2)],
            ['PT',                   number_format($totals['pt'], 2)],
            ['LWF',                  number_format($totals['lwf_emp']+$totals['lwf_er'], 2)],
            ['TDS',                  number_format($totals['tds'], 2)],
            ['Bonus Provision',      number_format($totals['bonus'], 2)],
            ['Gratuity Provision',   number_format($totals['gratuity'], 2)],
            ['Total CTC',            number_format($totals['ctc'], 2)],
        ]);

        $this->newLine();
        $this->info("Now visit:");
        $this->line("  /payroll/runs/{$run->run_id}                                # run summary");
        $this->line("  /reports/complete-salary?year={$year}&month={$month}        # Salary Simulation");
        $this->line("  /payroll/runs/{$run->run_id} → 'Generate Bank File'         # CSV download");

        return self::SUCCESS;
    }
}
