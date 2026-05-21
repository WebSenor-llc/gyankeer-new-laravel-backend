<?php

namespace App\Console\Commands;

use App\Models\LeaveBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Inspect one employee's leave balance + ledger.
 *
 *   php artisan leave:check 13008
 *
 * Shows the FY 2026-27 balances row + every ledger entry, and verifies
 * that sum(ledger) for the FY matches availed_ytd on the balance row.
 */
class CheckLeaveBalance extends Command
{
    protected $signature = 'leave:check {emp} {--fy=2027}';
    protected $description = 'Inspect leave balances + ledger for one employee';

    public function handle(): int
    {
        $emp = (int) $this->argument('emp');
        $fy  = (int) $this->option('fy');

        $this->line("");
        $this->info("=== Employee {$emp}  (FY " . ($fy - 1) . "-" . substr($fy, -2) . ") ===");

        $this->line("");
        $this->line("— Leave balances —");
        $balances = LeaveBalance::where('emp_id', $emp)->where('fy', $fy)->get();
        if ($balances->isEmpty()) {
            $this->warn("  (no balance rows — emp_id may not be in the CSV import)");
        }
        foreach ($balances as $b) {
            $this->line(sprintf(
                "  %s: opening=%-6s availed_ytd=%-6s closing=%s",
                $b->leave_code, $b->opening_balance, $b->availed_ytd, $b->closing_balance
            ));
        }

        $this->line("");
        $this->line("— Ledger entries (oldest first) —");
        $ledger = DB::table('leave_ledger')
            ->where('emp_id', $emp)
            ->orderBy('period_year')
            ->orderBy('period_month')
            ->orderBy('leave_code')
            ->get();
        if ($ledger->isEmpty()) {
            $this->warn("  (no ledger rows — payroll has not been run for this emp since the hook was added)");
        }
        foreach ($ledger as $r) {
            $this->line(sprintf(
                "  %s %d-%02d: %.2f days  (source: %s)",
                $r->leave_code, $r->period_year, $r->period_month,
                (float) $r->days_consumed, $r->source
            ));
        }

        $this->line("");
        $this->line("— Sum check (should equal availed_ytd above) —");
        $fyStartKey = ($fy - 1) * 12 + 4;
        $fyEndKey   = $fy * 12 + 3;
        foreach (['CL', 'PL', 'SL'] as $code) {
            $sum = (float) DB::table('leave_ledger')
                ->where('emp_id', $emp)
                ->where('leave_code', $code)
                ->whereRaw('(period_year * 12 + period_month) BETWEEN ? AND ?', [$fyStartKey, $fyEndKey])
                ->sum('days_consumed');
            $this->line(sprintf("  %s sum of FY ledger = %.2f", $code, $sum));
        }

        // ── Show why payroll may not have run for this emp ──
        $this->line("");
        $this->line("— Employee / payroll context —");
        $e = \App\Models\Employee::where('emp_id', $emp)->first();
        if (!$e) {
            $this->warn("  Employee NOT in employees table — payroll will never run.");
        } else {
            $this->line(sprintf("  company_id        = %s", $e->company_id ?? '(null)'));
            $this->line(sprintf("  salary_group_id   = %s", $e->salary_group_id ?? '(null)'));
            $this->line(sprintf("  salary_group_name = %s", optional($e->salary_group)->salary_group_name ?? '(none)'));
            $this->line(sprintf("  active_flag       = %s", $e->active_flag ? 'true' : 'FALSE — payroll skips inactive emps'));
        }

        // Apr 2026 attendance row
        $this->line("");
        $this->line("— April 2026 attendance_summary —");
        $att = \DB::table('attendance_summary')
            ->where('emp_id', $emp)
            ->where('period_year', 2026)
            ->where('period_month', 4)
            ->first();
        if (!$att) {
            $this->warn("  (no April 2026 attendance row — nothing to decrement)");
        } else {
            $this->line(sprintf("  P=%s W=%s PH=%s A=%s CL=%s PL=%s SL=%s HD=%s",
                $att->p_count ?? 0, $att->w_count ?? 0, $att->ph_count ?? 0,
                $att->a_count ?? 0, $att->cl_count ?? 0, $att->pl_count ?? 0,
                $att->sl_count ?? 0, $att->hd_count ?? 0));
        }

        // Apr 2026 payslip
        $this->line("");
        $this->line("— April 2026 payslip —");
        $slip = \DB::table('payslips as p')
            ->join('salary_runs as r', 'p.run_id', '=', 'r.run_id')
            ->where('p.emp_id', $emp)
            ->where('r.period_year', 2026)
            ->where('r.period_month', 4)
            ->select('p.payslip_id', 'p.gross_earnings', 'p.net_pay', 'p.generated_at')
            ->first();
        if (!$slip) {
            $this->warn("  (no April 2026 payslip — payroll has not been generated for this emp)");
            $this->warn("  → re-generate April for this emp's salary group (or use ⚡ Generate ALL Groups)");
        } else {
            $this->line(sprintf("  payslip_id=%s gross=%s net=%s generated_at=%s",
                $slip->payslip_id, $slip->gross_earnings, $slip->net_pay, $slip->generated_at));
        }

        $this->line("");
        return self::SUCCESS;
    }
}
