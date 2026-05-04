<?php

namespace Database\Seeders;

use App\Models\SalaryGroup;
use Illuminate\Database\Seeder;

/**
 * Renames the long, source-Excel salary group names to the short codes
 * used in conversation: Staff, ST1, ST2, Store, Security, Electrician Staff.
 *
 * Idempotent — `find by old name, rename to new` skips groups already renamed.
 *
 * Run:  php artisan db:seed --class=RenameSalaryGroupsSeeder
 */
class RenameSalaryGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $renames = [
            'GTPPL- Staff'                              => 'Staff',
            'GPPL - Sub. Staff -1'                      => 'ST1',
            'GPPL - Sub. Staff -2'                      => 'ST2',
            'GTPPL Store Department'                    => 'Store',
            'GTPPL -STAFF SECURITY'                     => 'Security',
            'GTPPL -  Electrician Staff  salary Sheet'  => 'Electrician Staff',
            'GTPPL-SM'                                  => 'Senior Management',
        ];

        $renamed = 0;
        foreach ($renames as $oldName => $newName) {
            $group = SalaryGroup::where('salary_group_name', $oldName)->first();
            if (!$group) continue;

            // If the new name already exists separately, skip rather than collide
            $existing = SalaryGroup::where('salary_group_name', $newName)->where('salary_group_id', '!=', $group->salary_group_id)->first();
            if ($existing) {
                $this->command->warn("Skip '{$oldName}': '{$newName}' already exists at id {$existing->salary_group_id}");
                continue;
            }

            $group->update(['salary_group_name' => $newName]);
            $renamed++;
            $this->command->info("Renamed '{$oldName}' → '{$newName}'");
        }

        $this->command->info("Done. Renamed {$renamed} salary groups.");
    }
}
