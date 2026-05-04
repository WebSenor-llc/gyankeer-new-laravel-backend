<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Imports 472 employees + their referenced master data (banks, salary groups,
 * departments, designations) from the JSON file at
 * database/seeders/data/employees.json (extracted from the Excel uploads).
 *
 * Run with:  php artisan db:seed --class=EmployeeImportSeeder
 */
class EmployeeImportSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/employees.json');
        if (!file_exists($jsonPath)) {
            $this->command->error("Missing: {$jsonPath}");
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        // Identify the GTPPL company (or create stub)
        $company = Company::firstOrCreate(
            ['company_code' => 'GTPPL'],
            ['company_name' => 'Gyankeer Tobacco Products Private Limited',
             'legal_name'   => 'Gyankeer Tobacco Products Private Limited',
             'active_flag'  => true,
             'status'       => 'Active']
        );

        // Pre-create banks, salary groups, departments, designations
        $bankMap = [];
        foreach ($data['banks'] as $name) {
            $b = Bank::firstOrCreate(
                ['bank_name' => $name],
                ['bank_code' => $this->codify($name, 6), 'active_flag' => true]
            );
            $bankMap[$name] = $b->bank_id;
        }
        $this->command->info("Banks: ".count($bankMap));

        $sgMap = [];
        foreach ($data['salary_groups'] as $name) {
            $sg = SalaryGroup::firstOrCreate(
                ['salary_group_name' => $name],
                ['company_id'  => $company->company_id,
                 'group_type'  => $this->guessGroupType($name),
                 'wage_periodicity' => 'Monthly',
                 'pf_applicable' => true, 'esi_applicable' => true, 'pt_applicable' => true,
                 'effective_from' => '2022-04-01', 'status' => 'Active']
            );
            $sgMap[$name] = $sg->salary_group_id;
        }
        $this->command->info("Salary groups: ".count($sgMap));

        $deptMap = [];
        foreach ($data['departments'] as $name) {
            $d = Department::firstOrCreate(
                ['dept_name' => $name],
                ['dept_code' => $this->codify($name, 8),
                 'company_id' => $company->company_id,
                 'active_flag' => true]
            );
            $deptMap[$name] = $d->dept_id;
        }
        $this->command->info("Departments: ".count($deptMap));

        $desigMap = [];
        foreach ($data['designations'] as $name) {
            $d = Designation::firstOrCreate(
                ['designation_name' => $name],
                ['designation_code' => $this->codify($name, 10),
                 'active_flag' => true]
            );
            $desigMap[$name] = $d->designation_id;
        }
        $this->command->info("Designations: ".count($desigMap));

        // Type lookup
        $typeLabel = ['ST' => 'Staff', 'SB' => 'Sub-Staff', 'WK' => 'Worker'];

        // Insert employees
        $inserted = 0;
        $skipped  = 0;
        foreach ($data['employees'] as $row) {
            try {
                $deptId = $row['_dept_name']     ? ($deptMap[$row['_dept_name']]      ?? null) : null;
                $sgId   = $row['_salary_group']  ? ($sgMap[$row['_salary_group']]     ?? null) : null;
                $bankId = $row['_bank_name']     ? ($bankMap[$row['_bank_name']]      ?? null) : null;
                $desigId= $row['_designation']   ? ($desigMap[$row['_designation']]   ?? null) : null;

                Employee::updateOrCreate(
                    ['emp_id' => $row['emp_id']],
                    [
                        'third_party_code'  => $row['third_party_code'],
                        'first_name'        => $this->trunc($row['first_name'], 100),
                        'last_name'         => $this->trunc($row['last_name'], 100),
                        'full_name'         => $this->trunc($row['full_name'], 200),
                        'fathers_name'      => $this->trunc($row['fathers_name'], 150),
                        'company_id'        => $company->company_id,
                        'dept_id'           => $deptId,
                        'salary_group_id'   => $sgId,
                        'bank_id'           => $bankId,
                        'designation_id'    => $desigId,
                        'employee_type'     => $this->trunc($typeLabel[$row['employee_type']] ?? $row['employee_type'], 50),
                        'employment_status' => $this->trunc($row['_status'] === 'Active' ? 'Active' : ($row['_status'] ?: 'Active'), 30),
                        'date_of_joining'   => $row['date_of_joining'],
                        'dob'               => $row['dob'],
                        'esi_ip_no'         => $this->trunc($row['esi_ip_no'], 30),
                        'uan'               => $this->trunc($row['uan'], 20),
                        'epf_member_id'     => $this->trunc($row['epf_member_id'], 30),
                        'current_basic'     => (float) $row['current_basic'],
                        'current_da'        => (float) $row['current_da'],
                        'current_hra'       => (float) $row['current_hra'],
                        'current_med'       => $this->trunc((string) ($row['current_med'] ?? ''), 50),
                        'current_conv'      => $this->trunc((string) ($row['current_conv'] ?? ''), 50),
                        'current_gross'     => (float) $row['current_gross'],
                        'current_ctc'       => (float) $row['current_ctc'],
                        'bank_account_no'   => $this->trunc($row['bank_account_no'], 30),
                        'bank_ifsc'         => $this->trunc($row['bank_ifsc'], 11),
                        'confirmed_flag'    => (bool) $row['confirmed_flag'],
                        'active_flag'       => (bool) $row['active_flag'],
                        'vpf_amount'        => (float) ($row['vpf_amount'] ?? 0),
                    ]
                );
                $inserted++;
            } catch (\Throwable $e) {
                $skipped++;
                $this->command->warn("Skip emp {$row['emp_id']}: " . $e->getMessage());
            }
        }

        $this->command->info("Imported {$inserted} employees, skipped {$skipped}.");
    }

    private function codify(string $name, int $len): string
    {
        $up = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($name));
        return substr($up ?: 'CODE', 0, $len);
    }

    private function guessGroupType(string $name): string
    {
        $n = strtolower($name);
        if (str_contains($n, 'staff'))      return 'Staff';
        if (str_contains($n, 'sub'))        return 'Sub-Staff';
        if (str_contains($n, 'worker') || str_contains($n, 'contractor') || str_contains($n, 'labour')) return 'Worker';
        return 'Staff';
    }

    private function trunc(?string $v, int $max): ?string
    {
        if ($v === null) return null;
        return mb_substr($v, 0, $max);
    }
}
