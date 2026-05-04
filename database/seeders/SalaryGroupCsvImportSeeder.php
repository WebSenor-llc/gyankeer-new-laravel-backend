<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SalaryGroup;
use Illuminate\Database\Seeder;

/**
 * Imports / updates salary groups from data/05_salary_groups.csv.
 *
 * CSV format:
 *   GroupName, BonusPer, ComonayCode
 *
 * The CSV's "ComonayCode" comes from the user's old SUGAM HR system and does
 * NOT match Hreasy's company_id. We map by GROUP NAME PREFIX instead:
 *   - "BSK …"  → BSK AGENCIES
 *   - everything else → Gyankeer Tobacco Products Private Limited
 *
 * Match strategy:
 *   - First by trimmed exact name (case-insensitive)
 *   - If not found, create a new SalaryGroup
 *   - Existing groups NOT in the CSV are left alone (no deletes — payroll
 *     history may still reference them)
 *
 *   php artisan db:seed --class=Database\\Seeders\\SalaryGroupCsvImportSeeder
 */
class SalaryGroupCsvImportSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('data/05_salary_groups.csv');
        if (!file_exists($csvPath)) {
            $this->command->error("CSV not found at: {$csvPath}");
            return;
        }

        // Resolve company IDs by name (fallback: any company)
        $bsk    = Company::where('company_name', 'like', '%BSK%')->value('company_id');
        $gtppl  = Company::where('company_name', 'like', '%Gyankeer%')->value('company_id')
               ?? Company::where('company_name', 'like', '%GTPPL%')->value('company_id');

        if (!$bsk)   $this->command->warn('BSK company not found — BSK groups will use Gyankeer.');
        if (!$gtppl) $this->command->warn('Gyankeer company not found — defaulting to company_id=1.');
        $defaultCompanyId = $gtppl ?? 1;

        $rows  = array_map('str_getcsv', file($csvPath));
        $header = array_map('trim', array_shift($rows));

        $created = 0; $updated = 0; $skipped = 0;

        foreach ($rows as $row) {
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                continue; // empty row
            }
            [$name, $bonus, $companyCode] = array_pad($row, 3, '');
            $name  = trim((string) $name);
            $bonus = is_numeric($bonus) ? (float) $bonus : 0.0;

            if ($name === '') { $skipped++; continue; }

            // Map company by NAME PREFIX (CSV's ComonayCode is unreliable)
            $upper = strtoupper($name);
            if (str_starts_with($upper, 'BSK')) {
                $companyId  = $bsk ?: $defaultCompanyId;
                $companyTxt = 'BSK AGENCIES';
            } else {
                $companyId  = $defaultCompanyId;
                $companyTxt = 'Gyankeer Tobacco Products Private Limited';
            }

            // Find existing by case-insensitive trimmed name
            $existing = SalaryGroup::whereRaw('LOWER(TRIM(salary_group_name)) = ?', [strtolower($name)])
                ->first();

            if ($existing) {
                $existing->update([
                    'salary_group_name' => $name,
                    'bonus_per'         => $bonus,
                    'company_id'        => $companyId,
                    'under_company'     => $companyTxt,
                    'status'            => 'Active',
                ]);
                $updated++;
            } else {
                SalaryGroup::create([
                    'salary_group_name' => $name,
                    'bonus_per'         => $bonus,
                    'company_id'        => $companyId,
                    'under_company'     => $companyTxt,
                    'status'            => 'Active',
                    'pf_applicable'     => true,
                    'esi_applicable'    => true,
                    'pt_applicable'     => false,   // Rajasthan — no PT
                    'lwf_applicable'    => false,   // Rajasthan — no LWF
                    'gratuity_applicable' => true,
                    'effective_from'    => '2008-04-12',
                ]);
                $created++;
            }
        }

        $this->command->info("Salary groups: {$created} created, {$updated} updated, {$skipped} skipped (empty rows).");
    }
}
