<?php

namespace Database\Seeders;

use App\Models\SalaryGroup;
use Illuminate\Database\Seeder;

/**
 * Reverses RenameSalaryGroupsSeeder — restores the original long names
 * imported from the Excel master file.
 *
 * Run only if you already executed RenameSalaryGroupsSeeder and want to
 * revert:
 *   php artisan db:seed --class=RollbackSalaryGroupsSeeder
 *
 * Idempotent: skips groups whose short name no longer exists.
 */
class RollbackSalaryGroupsSeeder extends Seeder
{
    public function run(): void
    {
        // Reverse mapping: short → original long
        $reverts = [
            'Staff'              => 'GTPPL- Staff',
            'ST1'                => 'GPPL - Sub. Staff -1',
            'ST2'                => 'GPPL - Sub. Staff -2',
            'Store'              => 'GTPPL Store Department',
            'Security'           => 'GTPPL -STAFF SECURITY',
            'Electrician Staff'  => 'GTPPL -  Electrician Staff  salary Sheet',
            'Senior Management'  => 'GTPPL-SM',
        ];

        $reverted = 0;
        foreach ($reverts as $shortName => $originalName) {
            $group = SalaryGroup::where('salary_group_name', $shortName)->first();
            if (!$group) continue;

            $existing = SalaryGroup::where('salary_group_name', $originalName)
                ->where('salary_group_id', '!=', $group->salary_group_id)
                ->first();
            if ($existing) {
                $this->command->warn("Skip '{$shortName}': '{$originalName}' already exists at id {$existing->salary_group_id}");
                continue;
            }

            $group->update(['salary_group_name' => $originalName]);
            $reverted++;
            $this->command->info("Reverted '{$shortName}' → '{$originalName}'");
        }

        $this->command->info("Done. Reverted {$reverted} salary groups to original names.");
    }
}
