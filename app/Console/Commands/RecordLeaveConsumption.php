<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Manually record (or correct) a single month's leave consumption for one
 * employee and recompute their balance — without going through payroll.
 *
 *   php artisan leave:record 13008 --year=2026 --month=4 --cl=4 --pl=2
 *   php artisan leave:record 13008 --year=2026 --month=4 --sl=1.5
 *
 * Re-running for the same emp × code × period overwrites the ledger row
 * (UNIQUE index), so balances never double-deduct.
 */
class RecordLeaveConsumption extends Command
{
    protected $signature = 'leave:record
        {emp : Employee ID}
        {--year= : Period year (e.g. 2026)}
        {--month= : Period month (1-12)}
        {--cl=0 : CL days taken}
        {--pl=0 : PL days taken}
        {--sl=0 : SL days taken}
        {--fy=2027 : FY end year to update balances under}';
    protected $description = 'Manually record leaves taken in a month + recompute balance';

    public function handle(): int
    {
        $emp   = (int) $this->argument('emp');
        $year  = (int) $this->option('year');
        $month = (int) $this->option('month');
        $cl    = (float) $this->option('cl');
        $pl    = (float) $this->option('pl');
        $sl    = (float) $this->option('sl');
        $fy    = (int) $this->option('fy');

        if (!$year || !$month) {
            $this->error('You must pass --year and --month.');
            return self::FAILURE;
        }

        $e = \App\Models\Employee::where('emp_id', $emp)->first();
        if (!$e) {
            $this->error("Employee {$emp} not found in employees table.");
            return self::FAILURE;
        }

        foreach (['CL' => $cl, 'PL' => $pl, 'SL' => $sl] as $code => $days) {
            DB::table('leave_ledger')->updateOrInsert(
                [
                    'emp_id'       => $emp,
                    'leave_code'   => $code,
                    'period_year'  => $year,
                    'period_month' => $month,
                ],
                [
                    'company_id'    => $e->company_id,
                    'days_consumed' => $days,
                    'source'        => 'manual',
                    'updated_at'    => now(),
                    'created_at'    => now(),
                ]
            );

            // Recompute closing for this code in the picked FY
            $fyStartKey = ($fy - 1) * 12 + 4;
            $fyEndKey   = $fy * 12 + 3;
            $consumed = (float) DB::table('leave_ledger')
                ->where('emp_id', $emp)
                ->where('leave_code', $code)
                ->whereRaw('(period_year * 12 + period_month) BETWEEN ? AND ?', [$fyStartKey, $fyEndKey])
                ->sum('days_consumed');

            $bal = \App\Models\LeaveBalance::withTrashed()
                ->where('emp_id', $emp)
                ->where('leave_code', $code)
                ->where('fy', $fy)
                ->first();
            if ($bal) {
                $opening = (float) $bal->opening_balance;
                $accrued = (float) $bal->accrued_ytd;
                $bal->update([
                    'availed_ytd'     => (string) $consumed,
                    'closing_balance' => (string) ($opening + $accrued - $consumed),
                    'last_applied_date' => \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth(),
                ]);
                $this->line(sprintf("  %s: month=%.2f total_availed=%.2f closing=%.2f",
                    $code, $days, $consumed, $opening + $accrued - $consumed));
            } else {
                $this->warn("  {$code}: no leave_balance row for FY {$fy} — ledger written but balance not recomputed.");
            }
        }

        $this->info("\n✅ Recorded leave consumption for emp {$emp} in {$year}-{$month}.");
        return self::SUCCESS;
    }
}
