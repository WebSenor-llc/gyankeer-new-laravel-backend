<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeCareerEvent;
use Illuminate\Database\Seeder;

/**
 * Applies the FY 2025-26 v2 salary revision from the new increment Excel
 * (Updated new Increment sheet 2025-2026 staff - Copy (2).xlsx).
 *
 * Affects 345 contractor / worker employees:
 *   - 305 employees: Gross 10,400 → 10,920 (+5%)
 *   - 40 employees:  Gross  9,100 →  9,620 (+5.71%)
 *
 * For each employee:
 *   - Updates current_basic / hra / da / med / conv / spl / gross / ctc
 *   - Marks last_increment_date = today, captures old_gross / new_gross / pct
 *   - Writes an employee_career_events row (event_type = 'Increment v2')
 *
 * Idempotent — skips employees whose values already match the v2 numbers.
 *
 * Run:  php artisan db:seed --class=SalaryUpdateV2Seeder
 */
class SalaryUpdateV2Seeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/salary_updates_v2.json');
        if (!file_exists($jsonPath)) {
            $this->command->error("Missing data file: {$jsonPath}");
            return;
        }

        $rows = json_decode(file_get_contents($jsonPath), true) ?: [];

        $effectiveDate = '2026-04-01';   // start of FY 2026-27 — adjust if you want an older date
        $updated = 0;
        $skipped = 0;
        $missing = 0;

        foreach ($rows as $r) {
            $eid = (int) $r['emp_id'];
            $emp = Employee::where('emp_id', $eid)->first();
            if (!$emp) { $missing++; continue; }

            $oldGross  = (float) $emp->current_gross;
            $newGross  = (float) $r['new_total'];

            // Skip if already at v2 values (within 1 paisa tolerance)
            if (abs($oldGross - $newGross) < 0.5) { $skipped++; continue; }

            // Combine Education + HouseRent into spl_allow (consistent with import seeder)
            $newSpl = (float) $r['new_edu'] + (float) $r['new_houserent'];
            $hikePct = $oldGross > 0 ? round(($newGross - $oldGross) / $oldGross * 100, 2) : 0;

            $emp->update([
                'current_basic'   => (float) $r['new_basic'],
                'current_da'      => (float) $r['new_da'],
                'current_hra'     => (float) $r['new_hra'],
                'current_med'     => (float) $r['new_medical'],
                'current_conv'    => (float) $r['new_conv'],
                'current_spl'     => $newSpl,
                'current_gross'   => $newGross,
                'current_ctc'     => $newGross,
                'last_increment_date'      => $effectiveDate,
                'last_increment_pct'       => $hikePct,
                'last_increment_old_gross' => $oldGross,
                'last_increment_new_gross' => $newGross,
            ]);

            EmployeeCareerEvent::updateOrCreate(
                [
                    'emp_id'     => $eid,
                    'event_date' => $effectiveDate,
                    'event_type' => 'Increment v2',
                ],
                [
                    'old_value'    => sprintf('Gross %.2f', $oldGross),
                    'new_value'    => sprintf('Gross %.2f', $newGross),
                    'salary_old'   => $oldGross,
                    'salary_new'   => $newGross,
                    'hike_percent' => $hikePct,
                    'remarks'      => sprintf('FY 2025-26 v2 revision — group %s', $r['group'] ?? '—'),
                    'created_by'   => 'SalaryUpdateV2Seeder',
                ]
            );

            $updated++;
        }

        $this->command->info("Salary update v2: {$updated} updated, {$skipped} already at new values, {$missing} not found in DB.");
    }
}
