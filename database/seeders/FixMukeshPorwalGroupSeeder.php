<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\SalaryGroup;
use Illuminate\Database\Seeder;

/**
 * One-time fix: moves Mukesh Porwal (emp_id 13153) from the
 * `Retainership` salary group to `GTPPL- Staff`.
 *
 * Why: the ExportSheet of the Excel master mis-classified him under
 * Retainership, but the FY 2025-26 STAFF increment tab includes him
 * — meaning he is actually a Staff-tier employee.
 *
 * Run:  php artisan db:seed --class=FixMukeshPorwalGroupSeeder
 *
 * Idempotent: if the move has already been applied, the seeder reports
 * "already in target group" and exits cleanly.
 */
class FixMukeshPorwalGroupSeeder extends Seeder
{
    public function run(): void
    {
        $empId       = 13153;
        $targetGroup = 'GTPPL- Staff'; // also matches 'Staff' if you renamed it earlier

        $emp = Employee::where('emp_id', $empId)->first();
        if (!$emp) {
            $this->command->error("Employee {$empId} not found.");
            return;
        }

        // Resolve target salary group (by long or short name)
        $group = SalaryGroup::where('salary_group_name', $targetGroup)
            ->orWhere('salary_group_name', 'Staff')
            ->first();
        if (!$group) {
            $this->command->error("Salary group '{$targetGroup}' (or short 'Staff') not found.");
            return;
        }

        if ((int) $emp->salary_group_id === (int) $group->salary_group_id) {
            $this->command->info("Mukesh Porwal already in '{$group->salary_group_name}'. No change.");
            return;
        }

        $oldId   = $emp->salary_group_id;
        $oldName = SalaryGroup::where('salary_group_id', $oldId)->value('salary_group_name') ?? '—';

        $emp->update(['salary_group_id' => $group->salary_group_id]);

        $this->command->info(sprintf(
            "Moved emp %d (%s) from '%s' to '%s'.",
            $empId,
            $emp->full_name,
            $oldName,
            $group->salary_group_name
        ));
    }
}
