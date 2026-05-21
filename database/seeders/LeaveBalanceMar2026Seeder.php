<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Imports the 31-Mar-2026 PL/CL leave balances from
 *     database/data/leave_balances_mar2026.csv
 * into leave_balances. The CSV's NetBalance (what's left at FY-end) becomes
 * the OPENING balance for FY 2026-27 (fy = 2027). availed_ytd starts at 0
 * so the new fiscal year starts clean.
 *
 * The CSV columns are:
 *   EmployeeID, Year, LeaveCode, OpeningBalance, PreviousYearBalance,
 *   TotalBalance, Availed, NetBalance
 *
 * Re-runnable: upserts on (emp_id, leave_code, fy=2027). Re-running won't
 * stack duplicates — it just refreshes the opening to the latest CSV.
 */
class LeaveBalanceMar2026Seeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/leave_balances_mar2026.csv');
        if (!file_exists($path)) {
            $this->command->error("CSV not found at {$path}");
            return;
        }

        $fh = fopen($path, 'r');
        if (!$fh) {
            $this->command->error("Could not open {$path}");
            return;
        }

        $header = fgetcsv($fh);
        $idx = array_flip(array_map('trim', $header));

        $imported = 0;
        $orphaned = 0;   // CSV rows whose emp_id has no matching employee row
        $blank    = 0;   // CSV rows missing emp_id or code
        $orphanIds = [];

        // FY 2026-27 — i.e., 01-Apr-2026 to 31-Mar-2027. Store as the FY-end
        // year (2027) to match the existing fy convention.
        $fy = 2027;

        // Pre-fetch employee lookup so we resolve company_id + name once.
        // We DO NOT skip unknown employees anymore — the leave balance is
        // imported regardless. company_id stays NULL when the employee is
        // missing (matches the leave_balances column's nullable spec).
        $emps  = \App\Models\Employee::pluck('company_id', 'emp_id');
        $names = \App\Models\Employee::pluck('full_name', 'emp_id');

        while (($row = fgetcsv($fh)) !== false) {
            $empId   = (int) ($row[$idx['EmployeeID']]   ?? 0);
            $code    = trim($row[$idx['LeaveCode']]      ?? '');
            $netBal  = (float) ($row[$idx['NetBalance']]          ?? 0);

            if (!$empId || !$code) { $blank++; continue; }

            $isOrphan = !$emps->has($empId);
            if ($isOrphan) {
                $orphaned++;
                $orphanIds[] = $empId;
            }

            // NetBalance on 31-Mar-2026 carries into FY 2026-27 as opening.
            // accrued_ytd starts at 0 — accruals happen month by month.
            \App\Models\LeaveBalance::withTrashed()->updateOrCreate(
                [
                    'emp_id'     => $empId,
                    'leave_code' => $code,
                    'fy'         => $fy,
                ],
                [
                    'company_id'        => $emps->get($empId),       // NULL if orphan — fine
                    'employee_name'     => $names->get($empId, '(unknown — emp_id ' . $empId . ')'),
                    'opening_balance'   => (string) $netBal,         // carry-forward
                    'accrued_ytd'       => '0',
                    'availed_ytd'       => '0',
                    'encashed_ytd'      => '0',
                    'lapsed_ytd'        => '0',
                    'closing_balance'   => (string) $netBal,
                    'last_applied_date' => '2026-03-31',
                    'active_flag'       => true,
                    'deleted_at'        => null,
                ]
            );
            $imported++;
        }
        fclose($fh);

        $this->command->info("✅ Imported {$imported} leave-balance rows into FY 2026-27.");
        if ($orphaned > 0) {
            $uniqueOrphans = array_unique($orphanIds);
            $sample = array_slice($uniqueOrphans, 0, 8);
            $this->command->warn(
                "⚠  {$orphaned} row(s) for " . count($uniqueOrphans) .
                " emp_id(s) that don't exist in your `employees` table " .
                "(but were still imported — sample: " . implode(', ', $sample) .
                (count($uniqueOrphans) > 8 ? ', …' : '') . ")"
            );
        }
        if ($blank > 0) {
            $this->command->warn("⚠  {$blank} blank/malformed row(s) skipped.");
        }
    }
}
