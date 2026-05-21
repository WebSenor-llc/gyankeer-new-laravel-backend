<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-import leave consumption for many employees from a CSV.
 *
 * CSV format (with header row):
 *     emp_id,year,month,cl,pl,sl
 *     13008,2026,4,4,2,0
 *     13002,2026,4,1,0,0
 *     ...
 *
 * Notes:
 *   • Headers are case-insensitive and may be in any order.
 *   • Missing cl/pl/sl columns default to 0.
 *   • If --year / --month flags are passed, they OVERRIDE per-row values
 *     (handy when the CSV is for one specific month and you don't want to
 *     repeat it on every row).
 *   • Re-runnable: same (emp × code × period) row gets updated, not stacked.
 *   • Source is recorded as 'manual-bulk' so you can distinguish from
 *     'payroll' (auto from engine) and 'manual' (single-emp command).
 *
 * Usage:
 *     php artisan leave:record-bulk database/data/april_2026_leaves.csv
 *     php artisan leave:record-bulk path/to/file.csv --year=2026 --month=4
 */
class RecordLeaveConsumptionBulk extends Command
{
    protected $signature = 'leave:record-bulk
        {path}
        {--year=}
        {--month=}
        {--fy=2027}
        {--dry-run}';
    protected $description = 'Bulk-record leave consumption from a CSV and recompute balances';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (!str_starts_with($path, '/')) {
            $path = base_path($path);
        }
        if (!file_exists($path)) {
            $this->error("CSV not found: {$path}");
            return self::FAILURE;
        }

        $fyDefault = (int) $this->option('fy');
        $yearOverride  = $this->option('year') !== null ? (int) $this->option('year') : null;
        $monthOverride = $this->option('month') !== null ? (int) $this->option('month') : null;
        $dryRun = (bool) $this->option('dry-run');

        $fh = fopen($path, 'r');
        $rawHeader = fgetcsv($fh);
        if (!$rawHeader) {
            $this->error('CSV is empty or unreadable.');
            return self::FAILURE;
        }
        // Build case-insensitive header → column-index map
        $idx = [];
        foreach ($rawHeader as $i => $h) {
            $idx[strtolower(trim((string) $h))] = $i;
        }
        // Required column
        if (!isset($idx['emp_id'])) {
            $this->error("CSV header is missing 'emp_id' column. Got: " . implode(', ', $rawHeader));
            return self::FAILURE;
        }

        $touched = [];   // [empId][code] = total consumed in FY (recomputed at end)
        $rows    = 0;
        $bad     = 0;
        $unknown = [];

        // Pre-load employees for company_id lookup
        $emps = \App\Models\Employee::pluck('company_id', 'emp_id');

        while (($r = fgetcsv($fh)) !== false) {
            $rows++;
            $empId = (int) ($r[$idx['emp_id']] ?? 0);
            $year  = $yearOverride  ?? (int) ($r[$idx['year']]  ?? 0);
            $month = $monthOverride ?? (int) ($r[$idx['month']] ?? 0);
            $cl = (float) ($r[$idx['cl']] ?? 0);
            $pl = (float) ($r[$idx['pl']] ?? 0);
            $sl = (float) ($r[$idx['sl']] ?? 0);

            if (!$empId || !$year || $month < 1 || $month > 12) {
                $bad++;
                continue;
            }

            $companyId = $emps->get($empId);
            if ($companyId === null) $unknown[] = $empId;

            foreach (['CL' => $cl, 'PL' => $pl, 'SL' => $sl] as $code => $days) {
                if ($dryRun) continue;
                DB::table('leave_ledger')->updateOrInsert(
                    [
                        'emp_id'       => $empId,
                        'leave_code'   => $code,
                        'period_year'  => $year,
                        'period_month' => $month,
                    ],
                    [
                        'company_id'    => $companyId,
                        'days_consumed' => $days,
                        'source'        => 'manual-bulk',
                        'updated_at'    => now(),
                        'created_at'    => now(),
                    ]
                );
                $touched[$empId][$code] = true;
            }
        }
        fclose($fh);

        $this->info(sprintf("\nParsed %d row(s). Bad/blank: %d. Unknown emp_ids: %d.",
            $rows, $bad, count(array_unique($unknown))));

        if ($dryRun) {
            $this->warn('Dry-run — no ledger writes made and no balances recomputed.');
            return self::SUCCESS;
        }

        // ── Recompute balances for every touched emp × code ──
        $fyStartKey = ($fyDefault - 1) * 12 + 4;
        $fyEndKey   = $fyDefault * 12 + 3;
        $balancesUpdated = 0;
        $balancesMissing = 0;

        foreach ($touched as $empId => $codes) {
            foreach (array_keys($codes) as $code) {
                $consumed = (float) DB::table('leave_ledger')
                    ->where('emp_id', $empId)
                    ->where('leave_code', $code)
                    ->whereRaw('(period_year * 12 + period_month) BETWEEN ? AND ?', [$fyStartKey, $fyEndKey])
                    ->sum('days_consumed');

                $bal = \App\Models\LeaveBalance::withTrashed()
                    ->where('emp_id', $empId)
                    ->where('leave_code', $code)
                    ->where('fy', $fyDefault)
                    ->first();

                if (!$bal) { $balancesMissing++; continue; }

                $opening = (float) $bal->opening_balance;
                $accrued = (float) $bal->accrued_ytd;
                $bal->update([
                    'availed_ytd'     => (string) $consumed,
                    'closing_balance' => (string) ($opening + $accrued - $consumed),
                ]);
                $balancesUpdated++;
            }
        }

        $this->info(sprintf("✅ Recomputed %d balance row(s). %d emp×code combos had no leave_balance row (ledger written but balance not updated — run the LeaveBalanceMar2026Seeder first if needed).",
            $balancesUpdated, $balancesMissing));

        if (!empty($unknown)) {
            $sample = array_slice(array_unique($unknown), 0, 8);
            $this->warn("⚠  Emp IDs not in employees table (ledger still written, company_id is NULL): "
                . implode(', ', $sample) . (count(array_unique($unknown)) > 8 ? ', …' : ''));
        }

        return self::SUCCESS;
    }
}
