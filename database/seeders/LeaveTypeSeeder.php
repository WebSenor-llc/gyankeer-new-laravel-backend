<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['EL',  'Earned Leave',     'Annual',  21, 'Monthly accrual 1.75/mo', 1, 30, true, 60, 12, 'ALL', 7,  'Paid','Factories Act §79'],
            ['CL',  'Casual Leave',     'Annual',   7, 'Annual upfront',          1,  3, false, 0, 3,  'ALL', 0,  'Paid','Org policy'],
            ['SL',  'Sick Leave',       'Annual',  12, 'Annual upfront',          1, 15, false, 0, 3,  'ALL', 3,  'Paid','S&E Act'],
            ['ML',  'Maternity Leave',  'Special',182, 'Per pregnancy',         182,182, false, 0, 80, 'F',   0,  'Paid','Maternity Benefit Act 2017'],
            ['PL',  'Paternity Leave',  'Special',  5, 'Per child event',         1,  5, false, 0, 12, 'M',   0,  'Paid','Org policy'],
            ['AL',  'Adoption Leave',   'Special', 84, 'Per adoption',           84, 84, false, 0, 12, 'ALL', 0,  'Paid','MB Amendment Act'],
            ['COMP','Comp Off',         'Earned',   0, 'OT in lieu',              1,  1, false, 0, 0,  'ALL', 0,  'Paid','Factories Act §59'],
            ['BL',  'Bereavement',      'Special',  5, 'Per event',               1,  5, false, 0, 0,  'ALL', 0,  'Paid','Org policy'],
            ['MARR','Marriage',         'Special',  3, 'Once in service',         1,  3, false, 0, 0,  'ALL', 0,  'Paid','Org policy'],
            ['LWP', 'Loss of Pay',      'Unpaid',   0, 'On approval',             1, 90, false, 0, 0,  'ALL', 0,  'Unpaid','—'],
            ['POSH','POSH Leave',       'Special', 90, 'Per case',                1, 90, false, 0, 0,  'F',   0,  'Paid','SHW Act 2013'],
        ];
        foreach ($rows as [$c,$n,$cat,$q,$acc,$min,$max,$enc,$cf,$elig,$gen,$med,$pay,$act]) {
            LeaveType::create([
                'leave_code'                => $c,
                'leave_name'                => $n,
                'category'                  => $cat,
                'annual_quota'              => $q,
                'accrual_method'            => $acc,
                'min_days_per_application'  => $min,
                'max_continuous_days'       => $max,
                'encashable'                => $enc,
                'carry_forward_max'         => $cf,
                'eligibility_after_doj_months' => $elig,
                'applies_to_genders'        => $gen,
                'requires_medical_certificate_after_days' => $med,
                'paid_unpaid'               => $pay,
                'statutory_act'             => $act,
                'active_flag'               => true,
            ]);
        }
    }
}
