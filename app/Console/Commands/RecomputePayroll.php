<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\SalaryRun;
use App\Services\PayrollEngine;
use Illuminate\Console\Command;

/**
 *   php artisan payroll:recompute 4              # recompute SalaryRun id 4
 *   php artisan payroll:recompute --latest       # recompute the most recent run
 *   php artisan payroll:recompute --year=2026 --month=4 --company=1
 *
 * Wipes existing payslips for the run and re-runs the engine with the
 * current calculator logic (CoSS 2020 wages for PF/ESI, attendance-driven
 * proration, etc.).
 */
class RecomputePayroll extends Command
{
    protected $signature = 'payroll:recompute
                            {runId? : Salary run id}
                            {--latest : Recompute the most recent run}
                            {--year= : Period year (with --month)}
                            {--month= : Period month (with --year)}
                            {--company= : Company id (default 1)}';

    protected $description = 'Recompute payslips for a salary run with current engine logic.';

    public function handle(PayrollEngine $engine): int
    {
        $run = $this->resolveRun();
        if (!$run) return self::FAILURE;

        $this->info("Recomputing run #{$run->run_id} — {$run->run_code} (Company {$run->company_id}, {$run->period_month}/{$run->period_year}, status {$run->status})");

        // Wipe existing payslips
        $deleted = Payslip::where('run_id', $run->run_id)->delete();
        $this->line("  Wiped {$deleted} existing payslips");

        $employees = Employee::where('company_id', $run->company_id)
            ->where('active_flag', true)
            ->get();
        $this->line("  Found {$employees->count()} active employees");

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        $totals = [
            'earnings' => 0, 'deductions' => 0, 'net' => 0, 'ctc' => 0,
            'pf_emp' => 0, 'pf_er' => 0, 'eps' => 0, 'edli' => 0, 'admin' => 0,
            'esi_emp' => 0, 'esi_er' => 0, 'pt' => 0, 'lwf_emp' => 0, 'lwf_er' => 0,
            'tds' => 0, 'bonus' => 0, 'gratuity' => 0,
        ];
        $skipped = 0;

        foreach ($employees as $emp) {
            try {
                if (!$emp->current_gross && !$emp->current_basic) { $skipped++; $bar->advance(); continue; }
                $p = $engine->computeForEmployee($emp, $run);
                $totals['earnings']    += $p->gross_earnings;
                $totals['deductions']  += $p->total_deductions;
                $totals['net']         += $p->net_pay;
                $totals['ctc']         += $p->total_employer_cost;
                $totals['pf_emp']      += $p->epf_emp;
                $totals['pf_er']       += $p->employer_pf;
                $totals['eps']         += $p->eps;
                $totals['edli']        += $p->edli;
                $totals['admin']       += $p->pf_admin;
                $totals['esi_emp']     += $p->esi_emp;
                $totals['esi_er']      += $p->employer_esi;
                $totals['pt']          += $p->pt;
                $totals['lwf_emp']     += $p->lwf_emp;
                $totals['lwf_er']      += $p->lwf_employer;
                $totals['tds']         += $p->tds;
                $totals['bonus']       += $p->bonus;
                $totals['gratuity']    += $p->gratuity_provision;
            } catch (\Throwable $ex) {
                $skipped++;
                $this->newLine();
                $this->warn("  Skipped {$emp->emp_id}: " . substr($ex->getMessage(), 0, 80));
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
        $this->info("Done. {$generated} payslips generated, {$skipped} skipped.");
        $this->table(
            ['Metric', 'Amount (₹)'],
            [
                ['Total Gross',            number_format($totals['earnings'], 2)],
                ['Total Deductions',       number_format($totals['deductions'], 2)],
                ['Total Net Payout',       number_format($totals['net'], 2)],
                ['Employee EPF (12%)',     number_format($totals['pf_emp'], 2)],
                ['Employer EPF (3.67%)',   number_format($totals['pf_er'], 2)],
                ['EPS (8.33%)',            number_format($totals['eps'], 2)],
                ['EDLI (0.5%)',            number_format($totals['edli'], 2)],
                ['Admin (0.5%)',           number_format($totals['admin'], 2)],
                ['Employee ESI (0.75%)',   number_format($totals['esi_emp'], 2)],
                ['Employer ESI (3.25%)',   number_format($totals['esi_er'], 2)],
                ['PT',                     number_format($totals['pt'], 2)],
                ['LWF (Emp + Er)',         number_format($totals['lwf_emp'] + $totals['lwf_er'], 2)],
                ['TDS',                    number_format($totals['tds'], 2)],
                ['Bonus Provision',        number_format($totals['bonus'], 2)],
                ['Gratuity Provision',     number_format($totals['gratuity'], 2)],
                ['Total Employer Cost',    number_format($totals['ctc'], 2)],
            ]
        );
        return self::SUCCESS;
    }

    private function resolveRun(): ?SalaryRun
    {
        if ($this->argument('runId')) {
            $r = SalaryRun::find($this->argument('runId'));
            if (!$r) $this->error("Run id {$this->argument('runId')} not found.");
            return $r;
        }
        if ($this->option('latest')) {
            $r = SalaryRun::orderByDesc('period_year')->orderByDesc('period_month')->first();
            if (!$r) $this->error("No salary runs exist.");
            return $r;
        }
        if ($this->option('year') && $this->option('month')) {
            $cid = (int) ($this->option('company') ?: 1);
            $r = SalaryRun::where('company_id', $cid)
                ->where('period_year', (int) $this->option('year'))
                ->where('period_month', (int) $this->option('month'))
                ->first();
            if (!$r) $this->error("No run for company {$cid}, {$this->option('year')}/{$this->option('month')}.");
            return $r;
        }
        $this->error('Specify a run id, --latest, or --year + --month.');
        return null;
    }
}
