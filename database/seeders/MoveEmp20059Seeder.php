<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-shot fix: move emp 20059 (Gopal Singh Kitawat) from
 * salary_group_id 1014 (Contractor - Hari Singh Kitawat) to
 * salary_group_id 1015 (Contractor - Lal Singh Kitawat).
 *
 * Uses raw DB::table() to bypass any model casting / observer.
 *
 * Run once:
 *   php artisan db:seed --class=Database\\Seeders\\MoveEmp20059Seeder
 */
class MoveEmp20059Seeder extends Seeder
{
    public function run(): void
    {
        $before = DB::table('employees')->where('emp_id', 20059)->first();
        if (!$before) {
            $this->command->error('❌ Emp 20059 not found in employees table.');
            return;
        }
        $oldGroupId = $before->salary_group_id;
        $oldGroupName = DB::table('salary_groups')->where('salary_group_id', $oldGroupId)->value('salary_group_name') ?? '—';

        $target = DB::table('salary_groups')->where('salary_group_id', 1015)->first();
        if (!$target) {
            $this->command->error('❌ Salary group id=1015 not found.');
            return;
        }

        $rowsAffected = DB::table('employees')
            ->where('emp_id', 20059)
            ->update([
                'salary_group_id' => 1015,
                'updated_at'      => now(),
            ]);

        $after = DB::table('employees')->where('emp_id', 20059)->first();
        $newGroupName = DB::table('salary_groups')->where('salary_group_id', $after->salary_group_id)->value('salary_group_name');

        $this->command->info("Rows affected: {$rowsAffected}");
        $this->command->info("Before: emp 20059 {$before->full_name} → [{$oldGroupId}] {$oldGroupName}");
        $this->command->info("After : emp 20059 {$after->full_name} → [{$after->salary_group_id}] {$newGroupName}");

        if ($after->salary_group_id == 1015) {
            $this->command->info('✅ Move confirmed in DB.');
        } else {
            $this->command->error('❌ Update did not persist! Row still shows group_id ' . $after->salary_group_id);
        }
    }
}
