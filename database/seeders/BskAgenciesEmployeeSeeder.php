<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryGroup;
use Illuminate\Database\Seeder;

/**
 * Imports the BSK Agencies (company_id = 3) initial roster:
 *   - 2 staff (BSK- Staff)         : 10001 Sardar Singh, 10004 Raju Lal Gameti
 *   - 10 workers (BSK -Company Labour): 10006-11018
 *
 * All workers share the same salary structure: Basic 7,800 + HRA 3,120 = 10,920.
 * Staff structures pulled from the SUGAM salary register screenshot.
 *
 * Run: php artisan db:seed --class=Database\\Seeders\\BskAgenciesEmployeeSeeder
 */
class BskAgenciesEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $bskCompanyId = 3;

        // Resolve salary groups by name (case-insensitive) — handles seeded
        // typos and varied whitespace from the CSV import.
        $bskStaff   = SalaryGroup::whereRaw("LOWER(TRIM(salary_group_name)) LIKE 'bsk%staff%'")
            ->where('salary_group_name', 'not like', '%Labour%')
            ->orderBy('salary_group_id')->first();
        $bskWorker  = SalaryGroup::whereRaw("LOWER(TRIM(salary_group_name)) LIKE 'bsk%labour%'")
            ->orderBy('salary_group_id')->first();

        if (!$bskStaff || !$bskWorker) {
            $this->command->error('❌ BSK salary groups not found. Run salary-group seeder first.');
            $this->command->info('   Available BSK groups: ' . SalaryGroup::where('salary_group_name','like','%BSK%')->pluck('salary_group_name')->implode(', '));
            return;
        }
        $this->command->info("Staff group  → [{$bskStaff->salary_group_id}] {$bskStaff->salary_group_name}");
        $this->command->info("Worker group → [{$bskWorker->salary_group_id}] {$bskWorker->salary_group_name}");

        // Resolve common designations (create if missing)
        $supervisor = Designation::firstOrCreate(['designation_name' => 'Supervisor']);
        $driver     = Designation::firstOrCreate(['designation_name' => 'Driver']);
        $labour     = Designation::firstOrCreate(['designation_name' => 'Labour']);
        $unskilled  = Designation::firstOrCreate(['designation_name' => 'Unskilled Labour']);

        // ── 2 STAFF (BSK- Staff) ───────────────────────────────────────
        $staff = [
            [
                'emp_id' => 10001, 'name' => 'Sardar Singh', 'father' => 'Samant Singh',
                'mother' => 'Sohan Bai', 'dob' => '1973-03-04', 'doj' => '2022-07-01',
                'aadhar' => '410984078588', 'mobile' => '8769052302', 'pan' => null,
                'address' => 'Solankiyo Ki Bhagal, Lal Madri, Rajsamand (Raj)-313301',
                'designation_id' => $supervisor->designation_id,
                'job_desc' => 'Supervisor',
                'epf_member_id' => 'RJUDR2713088000010011',
                'uan' => '100380018692',
                'esi_ip_no' => '1606447204',
                'basic' => 11864.60, 'hra' => 5393.00, 'da' => 0,
                'conv' => 862.88, 'med' => 539.30, 'spl' => 3093.30,  // edu 539.30 + sphr 2554 = 3093.30
                'gross' => 21753.00,
            ],
            [
                'emp_id' => 10004, 'name' => 'Raju Lal Gameti', 'father' => 'Chunni Lal',
                'mother' => 'Rodi Bai', 'dob' => '1982-02-22', 'doj' => '2022-07-01',
                'aadhar' => '455060932523', 'mobile' => '9636007620', 'pan' => null,
                'address' => 'W. No.- 8, Bhil Basti, Upli Oden, Nathdwara, Rajsamand (Raj)-313301',
                'designation_id' => $driver->designation_id,
                'job_desc' => 'Driver',
                'epf_member_id' => 'RJUDR2713088000010010',
                'uan' => '100318524105',
                'esi_ip_no' => '1606447089',
                'basic' => 8177.40, 'hra' => 3717.00, 'da' => 0,
                'conv' => 594.72, 'med' => 371.70, 'spl' => 1907.70,  // edu 371.70 + sphr 1536 = 1907.70
                'gross' => 14769.00,
            ],
        ];

        foreach ($staff as $s) {
            $this->upsertEmployee($s, $bskStaff->salary_group_id, $bskCompanyId, 'Staff');
        }

        // ── 10 WORKERS (BSK -Company Labour) ──────────────────────────
        // All share Basic 7800 + HRA 3120 = 10920 gross
        $workers = [
            ['emp_id' => 10006, 'name' => 'Nemi Chand Bhil',     'father' => 'Ghisa Bhil',          'mother' => 'Ganga Bai',   'dob' => '1996-01-01', 'doj' => '2024-09-01', 'aadhar' => '311217684291', 'pan' => 'GNJPB5852F', 'mobile' => '7627008249', 'address' => 'Vill Bhat Wala Basti Varni, Udaipur 313204', 'designation_id' => $labour->designation_id,    'job_desc' => 'Labour'],
            ['emp_id' => 10007, 'name' => 'Roop Singh Devra',     'father' => 'Bhanwar Singh Devra', 'mother' => 'Jyoti Bai',   'dob' => '1984-01-01', 'doj' => '2024-09-01', 'aadhar' => '715228276753', 'pan' => 'AUWPR1830R', 'mobile' => '9672007753', 'address' => 'Vill - Devra Ki Bhagal, VTC Pasuniya, PO Tantol, Rajsamand 313301', 'designation_id' => $labour->designation_id, 'job_desc' => 'Labour'],
            ['emp_id' => 10008, 'name' => 'Kan Singh Jhala',      'father' => 'Hari Singh Jhala',    'mother' => 'Laher Kunwar','dob' => '1993-05-09', 'doj' => '2024-09-01', 'aadhar' => '497571014291', 'pan' => 'BOVPJ0311K', 'mobile' => '7073002130', 'address' => 'Vill Choti Khedi, Udaipur 313204', 'designation_id' => $labour->designation_id, 'job_desc' => 'Labour'],
            ['emp_id' => 10009, 'name' => 'Gopal Singh Rajput',   'father' => 'Chaman Singh Rajput', 'mother' => 'Kallu Kanwar','dob' => '1993-01-01', 'doj' => '2024-09-01', 'aadhar' => '672027962994', 'pan' => 'FFNPR4517E', 'mobile' => '8290119360', 'address' => 'Vill Suro Ka Dhana, Thamla, Udaipur 313204', 'designation_id' => $labour->designation_id, 'job_desc' => 'Labour'],
            ['emp_id' => 11001, 'name' => 'Khum Singh',           'father' => 'Nathu Singh',         'mother' => 'Suraj Kunwar','dob' => '1988-06-05', 'doj' => '2022-07-01', 'aadhar' => '429596881754', 'pan' => null,         'mobile' => '9772733168', 'address' => 'Wada Wavdi, Jawar, Udaipur (Raj)-313204', 'designation_id' => $unskilled->designation_id, 'job_desc' => 'Unskilled Labour'],
            ['emp_id' => 11004, 'name' => 'Shobha Lal Gayri',     'father' => 'Mangi Lal',           'mother' => 'Sundar Bai',  'dob' => '1994-09-16', 'doj' => '2022-07-01', 'aadhar' => '200231445169', 'pan' => null,         'mobile' => '9660185075', 'address' => 'Gadvada, Bhansol, Udaipur (Raj)-313204', 'designation_id' => $unskilled->designation_id, 'job_desc' => 'Unskilled Labour'],
            ['emp_id' => 11013, 'name' => 'Lalit Singh Chouhan',  'father' => 'Pratap Singh',        'mother' => 'Rekha Kunwar','dob' => '1984-06-09', 'doj' => '2022-07-01', 'aadhar' => '604167923562', 'pan' => null,         'mobile' => '9602725802', 'address' => 'Karjiya, Gunjol, Rajsamand (Raj)-313301', 'designation_id' => $unskilled->designation_id, 'job_desc' => 'Unskilled Labour'],
            ['emp_id' => 11014, 'name' => 'Nahar Singh',          'father' => 'Unkar Singh',         'mother' => 'Raj Kunwar',  'dob' => '1983-01-01', 'doj' => '2022-07-01', 'aadhar' => '711152828979', 'pan' => null,         'mobile' => '9610111755', 'address' => 'Bawdi, Jawar, Udaipur (Raj)-313204', 'designation_id' => $unskilled->designation_id, 'job_desc' => 'Unskilled Labour'],
            ['emp_id' => 11016, 'name' => 'Narulal',              'father' => 'Lala Ji',             'mother' => 'Dhapu Bai',   'dob' => '1990-01-01', 'doj' => '2022-07-01', 'aadhar' => '433975409058', 'pan' => null,         'mobile' => '8107001290', 'address' => 'Khati Kheda, Thamla, Udaipur (Raj)-313204', 'designation_id' => $unskilled->designation_id, 'job_desc' => 'Unskilled Labour'],
            ['emp_id' => 11018, 'name' => 'Prem Lal',             'father' => 'Hamer Lal',           'mother' => 'Lali Bai',    'dob' => '1979-01-01', 'doj' => '2022-07-01', 'aadhar' => '803475870727', 'pan' => null,         'mobile' => '9166296936', 'address' => 'Papamal, Rajsamand (Raj)-313301', 'designation_id' => $unskilled->designation_id, 'job_desc' => 'Unskilled Labour'],
        ];

        foreach ($workers as $w) {
            $w['basic'] = 7800;
            $w['hra']   = 3120;
            $w['da']    = 0;
            $w['conv']  = 0;
            $w['med']   = 0;
            $w['spl']   = 0;
            $w['gross'] = 10920;   // 7800 + 3120
            $w['epf_member_id'] = null;
            $w['uan']           = null;
            $w['esi_ip_no']     = null;
            $this->upsertEmployee($w, $bskWorker->salary_group_id, $bskCompanyId, 'Worker');
        }

        $this->command->newLine();
        $this->command->info("✅ BSK Agencies roster imported.");
        $this->command->info("   2 staff @ varied salary, 10 workers @ ₹10,920 (Basic 7,800 + HRA 3,120)");
    }

    /**
     * Insert or update an employee with the given salary structure.
     */
    protected function upsertEmployee(array $data, int $salaryGroupId, int $companyId, string $type): void
    {
        $isStaff = $type === 'Staff';
        $employeeType = $isStaff ? 'Staff' : 'Worker';

        Employee::updateOrCreate(
            ['emp_id' => $data['emp_id']],
            [
                'third_party_code'  => (string) $data['emp_id'],
                'first_name'        => $data['name'],
                'last_name'         => '',
                'full_name'         => $data['name'],
                'fathers_name'      => $data['father'],
                'mothers_name'      => $data['mother'] ?? null,
                'dob'               => $data['dob'],
                'date_of_joining'   => $data['doj'],
                'gender'            => 'Male',
                'aadhar_id_no'      => $data['aadhar'] ?? null,
                'pan_no'            => $data['pan'] ?? null,
                'company_id'        => $companyId,
                'salary_group_id'   => $salaryGroupId,
                'designation_id'    => $data['designation_id'] ?? null,
                'employee_type'     => $employeeType,
                'employment_status' => 'Active',
                'active_flag'       => true,

                'epf_member_id'     => $data['epf_member_id'] ?? null,
                'uan'               => $data['uan'] ?? null,
                'esi_ip_no'         => $data['esi_ip_no'] ?? null,

                'personal_mobile'        => $data['mobile'] ?? null,
                'permanent_address_line1' => $data['address'] ?? null,
                'mailing_address_line1'   => $data['address'] ?? null,

                'current_basic'     => $data['basic'],
                'current_da'        => $data['da'],
                'current_hra'       => $data['hra'],
                'current_conv'      => $data['conv'],
                'current_med'       => $data['med'],
                'current_spl'       => $data['spl'],
                'current_gross'     => $data['gross'],

                'pt_state'          => 'RJ',
                'lwf_state'         => 'RJ',
            ]
        );

        $this->command->info("  ✓ {$data['emp_id']} {$data['name']} — Gross ₹" . number_format($data['gross'], 2) . "  ({$type})");
    }
}
