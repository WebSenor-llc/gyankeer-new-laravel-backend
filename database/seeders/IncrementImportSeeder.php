<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeCareerEvent;
use Illuminate\Database\Seeder;

/**
 * Imports the FY 2025-26 increment data from tabs 4-9 (STAFF / STORE /
 * ELECTRICIAN / SECURITY / ST 1 / ST II) of the Updated Increment Sheet.
 *
 * For each row, updates the matching employees row with:
 *   - last_increment_date  = 2025-04-01 (FY start)
 *   - last_increment_pct   = hike% (e.g. 8.00 for 8%)
 *   - last_increment_old_gross = old monthly gross (2025 levels)
 *   - last_increment_new_gross = new monthly gross (proposed)
 *   - current_basic / current_da / current_hra / current_conv / current_med /
 *     current_spl / current_gross / current_ctc — overwritten with proposed
 *     FY 2025-26 values (monthly).
 *
 * Also writes a row to employee_career_events recording the increment.
 *
 * Run:  php artisan db:seed --class=IncrementImportSeeder
 */
class IncrementImportSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/increments.json');
        if (!file_exists($jsonPath)) {
            $this->command->error("Missing: {$jsonPath}");
            return;
        }

        $rows     = json_decode(file_get_contents($jsonPath), true) ?: [];
        $updated  = 0;
        $missing  = 0;
        $events   = 0;

        foreach ($rows as $r) {
            $empId = (int) ($r['emp_id'] ?? 0);
            if (!$empId) continue;

            $emp = Employee::where('emp_id', $empId)->first();
            if (!$emp) {
                $missing++;
                continue;
            }

            $hikePct  = is_numeric($r['hike_pct'] ?? null) ? round(((float) $r['hike_pct']) * 100, 2) : null;
            $oldGross = is_numeric($r['old_gross'] ?? null) ? (float) $r['old_gross'] : null;
            $newGross = is_numeric($r['proposed_salary_m'] ?? null) ? (float) $r['proposed_salary_m'] : null;

            // Combined "Special Allowance" = Education + Special House Rent
            $pEdu  = is_numeric($r['p_edu']  ?? null) ? (float) $r['p_edu']  : 0;
            $pSphr = is_numeric($r['p_sphr'] ?? null) ? (float) $r['p_sphr'] : 0;
            $spl   = round($pEdu + $pSphr, 2);

            $update = [
                'last_increment_date'      => '2025-04-01',
                'last_increment_pct'       => $hikePct,
                'last_increment_old_gross' => $oldGross ?? 0,
                'last_increment_new_gross' => $newGross ?? 0,
            ];
            // Only overwrite components if the proposed breakdown was present
            if (is_numeric($r['p_gross'] ?? null) && (float) $r['p_gross'] > 0) {
                $update['current_basic']  = is_numeric($r['p_basic'] ?? null) ? (float) $r['p_basic'] : $emp->current_basic;
                $update['current_da']     = is_numeric($r['p_da']    ?? null) ? (float) $r['p_da']    : $emp->current_da;
                $update['current_hra']    = is_numeric($r['p_hra']   ?? null) ? (float) $r['p_hra']   : $emp->current_hra;
                $update['current_conv']   = is_numeric($r['p_conv']  ?? null) ? (string) $r['p_conv'] : $emp->current_conv;
                $update['current_med']    = is_numeric($r['p_med']   ?? null) ? (string) $r['p_med']  : $emp->current_med;
                $update['current_spl']    = $spl;
                $update['current_gross']  = (float) $r['p_gross'];
                $update['current_ctc']    = is_numeric($r['annual_revised_salary'] ?? null)
                    ? (float) $r['annual_revised_salary'] / 12
                    : (float) $r['p_gross'];
            }

            $emp->update($update);
            $updated++;

            EmployeeCareerEvent::updateOrCreate(
                [
                    'emp_id'     => $empId,
                    'event_date' => '2025-04-01',
                    'event_type' => 'Increment',
                ],
                [
                    'old_value'          => $oldGross !== null ? sprintf('Gross %.2f', $oldGross) : null,
                    'new_value'          => $newGross !== null ? sprintf('Gross %.2f', $newGross) : null,
                    'salary_old'         => $oldGross ?? 0,
                    'salary_new'         => $newGross ?? 0,
                    'hike_percent'       => $hikePct,
                    'performance_rating' => $r['rating'] ?? null,
                    'remarks'            => sprintf('FY 2025-26 increment from %s tab', $r['source_tab'] ?? '—'),
                    'created_by'         => 'IncrementImportSeeder',
                ]
            );
            $events++;
        }

        $this->command->info("Increment import: updated {$updated} employees, {$events} career events created, {$missing} not found.");
    }
}
