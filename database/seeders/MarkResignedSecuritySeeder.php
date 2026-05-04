<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Marks security employees who have actually resigned as inactive so they
 * stop showing in active payroll lists / counts.
 *
 *   - 13089  Lal Singh Sisodiya  (Sr. Security Supervisor) → RESIGNED
 *
 * Run:
 *   php artisan db:seed --class=MarkResignedSecuritySeeder
 */
class MarkResignedSecuritySeeder extends Seeder
{
    public function run(): void
    {
        $resigned = [
            13089 => ['name' => 'Lal Singh Sisodiya', 'date' => '2025-03-31'],
        ];

        $updated = 0;
        foreach ($resigned as $empId => $info) {
            $emp = Employee::where('emp_id', $empId)->first();
            if (!$emp) {
                $this->command->warn("Emp {$empId} ({$info['name']}) not found — skipped.");
                continue;
            }
            $payload = [
                'employment_status' => 'Resigned',
                'active_flag'       => false,
            ];
            // Set the new exit columns if the migration has been run
            if (\Illuminate\Support\Facades\Schema::hasColumn('employees', 'date_of_relieving')) {
                $payload['date_of_relieving'] = $info['date'];
                $payload['exit_reason']       = 'Resigned';
                $payload['notice_served_flag']= true;
                $payload['exit_notes']        = 'Auto-marked from FY 2024-25 closure.';
            }
            $emp->update($payload);
            $updated++;
            $this->command->info("Marked {$empId} {$info['name']} as Resigned (last day: {$info['date']}).");
        }

        $this->command->info("Done. {$updated} employees marked as Resigned.");
    }
}
