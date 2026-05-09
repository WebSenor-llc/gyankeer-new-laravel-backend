<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\SalaryGroup;
use Illuminate\Database\Seeder;

/**
 * One-shot fix: move emp 20061 (Kishan Singh Chundawat) from
 * salary_group_id 1014 (Contractor - Hari Singh Kitawat) to
 * salary_group_id 1013 (Contractor - Narayan Nath).
 *
 * Run once:
 *   php artisan db:seed --class=Database\\Seeders\\MoveEmp20061Seeder
 */
class MoveEmp20061Seeder extends Seeder
{
    public function run(): void
    {
        $emp = Employee::with('salary_group')->where('emp_id', 20061)->first();
        if (!$emp) {
            $this->command->error('❌ Emp 20061 not found.');
            return;
        }

        $oldGroupId   = $emp->salary_group_id;
        $oldGroupName = $emp->salary_group->salary_group_name ?? '—';

        $target = SalaryGroup::find(1013);
        if (!$target) {
            $this->command->error('❌ Salary group id=1013 (Contractor - Narayan Nath) not found.');
            return;
        }

        $emp->update(['salary_group_id' => 1013]);
        $emp->refresh()->load('salary_group');

        $this->command->info("Before: emp 20061 {$emp->full_name} → [{$oldGroupId}] {$oldGroupName}");
        $this->command->info("After : emp 20061 {$emp->full_name} → [{$emp->salary_group_id}] {$emp->salary_group->salary_group_name}");
        $this->command->info('✅ Moved successfully.');
    }
}
