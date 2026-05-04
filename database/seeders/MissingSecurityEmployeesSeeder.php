<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryGroup;
use Illuminate\Database\Seeder;

/**
 * Imports two security employees who appear on the SECURITY tab of the
 * FY 2025-26 increment Excel but were missing from the master ExportSheet:
 *
 *   - 13158  Vikram Singh   (Father: Pratap Singh, DOJ 2026-04-04)
 *   - 13159  Pankaj Shrimali (Father: Giriraj Shrimali, DOJ 2026-04-01)
 *
 * Both joined GTPPL Security, FY 2025-26 monthly gross ₹10,500.
 *
 * Run:  php artisan db:seed --class=MissingSecurityEmployeesSeeder
 */
class MissingSecurityEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/missing_security.json');
        if (!file_exists($jsonPath)) {
            $this->command->error("Missing data file: {$jsonPath}");
            return;
        }
        $rows = json_decode(file_get_contents($jsonPath), true) ?: [];

        $company       = Company::where('company_code', 'GTPPL')->first();
        $securityGroup = SalaryGroup::where('salary_group_name', 'GTPPL -STAFF SECURITY')->first();
        $securityDept  = Department::where('dept_name', 'Security')->first();
        $secDesignation = Designation::where('designation_name', 'Security Guard')->first()
            ?? Designation::where('designation_name', 'like', '%security%')->first();

        $typeLabel = ['ST' => 'Staff', 'SB' => 'Sub-Staff', 'WK' => 'Worker'];

        $inserted = 0;
        foreach ($rows as $r) {
            $eid = (int) $r['emp_id'];
            if (!$eid) continue;

            $nm    = trim((string) ($r['name'] ?? ''));
            $parts = preg_split('/\s+/', $nm, 2);
            $first = $parts[0] ?? '';
            $last  = $parts[1] ?? '';

            // Total gross from the proposed components, also confirms newm
            $basic = (float) ($r['pBasic'] ?? 0);
            $da    = (float) ($r['pDA']    ?? 0);
            $hra   = (float) ($r['pHRA']   ?? 0);
            $conv  = (float) ($r['pConv']  ?? 0);
            $med   = (float) ($r['pMed']   ?? 0);
            $edu   = (float) ($r['pEdu']   ?? 0);
            $sphr  = (float) ($r['pSphr']  ?? 0);
            $spl   = round($edu + $sphr, 2);
            $gross = (float) ($r['newm']  ?? ($basic + $da + $hra + $conv + $med + $spl));
            $arev  = (float) ($r['arev']  ?? ($gross * 12));

            $doj = null;
            if (!empty($r['doj'])) {
                $doj = is_string($r['doj']) ? substr($r['doj'], 0, 10) : (string) $r['doj'];
            }

            Employee::updateOrCreate(
                ['emp_id' => $eid],
                [
                    'third_party_code'  => (string) $eid,
                    'first_name'        => $first,
                    'last_name'         => $last,
                    'full_name'         => $nm,
                    'fathers_name'      => trim((string) ($r['father'] ?? '')),
                    'company_id'        => $company?->company_id,
                    'salary_group_id'   => $securityGroup?->salary_group_id,
                    'dept_id'           => $securityDept?->dept_id,
                    'designation_id'    => $secDesignation?->designation_id,
                    'employee_type'     => $typeLabel[$r['type'] ?? 'SB'] ?? 'Sub-Staff',
                    'employment_status' => 'Active',
                    'date_of_joining'   => $doj,
                    'current_basic'     => $basic,
                    'current_da'        => $da,
                    'current_hra'       => $hra,
                    'current_conv'      => $conv,
                    'current_med'       => $med,
                    'current_spl'       => $spl,
                    'current_gross'     => $gross,
                    'current_ctc'       => $arev / 12,
                    'last_increment_date' => '2025-04-01',
                    'last_increment_pct'  => is_numeric($r['hike'] ?? null) ? round(((float) $r['hike']) * 100, 2) : 0,
                    'last_increment_old_gross' => (float) ($r['g25'] ?? 0),
                    'last_increment_new_gross' => $gross,
                    'active_flag'       => true,
                ]
            );
            $inserted++;
            $this->command->info("Imported {$eid} {$nm}");
        }

        $this->command->info("Done. Inserted/updated {$inserted} security employees.");
    }
}
