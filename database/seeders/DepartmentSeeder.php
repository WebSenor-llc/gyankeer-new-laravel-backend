<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $depts = [
            ['OPS',  'Operations',       'CC-1001'],
            ['MAINT','Maintenance',      'CC-1002'],
            ['SEC',  'Security',         'CC-1003'],
            ['HORT', 'Horticulture',     'CC-1004'],
            ['PACK', 'Packing',          'CC-1005'],
            ['STORE','Store',            'CC-1006'],
            ['EXC',  'Excise',           'CC-1007'],
            ['AF',   'Accounts/Finance', 'CC-1008'],
            ['HR',   'HR & Admin',       'CC-1009'],
            ['SALES','Sales & Marketing','CC-1010'],
            ['LOG',  'Logistics',        'CC-1011'],
        ];
        foreach ($depts as [$code, $name, $cc]) {
            Department::create([
                'dept_code'        => $code,
                'dept_name'        => $name,
                'cost_center_code' => $cc,
                'company_id'       => 1,
                'active_flag'      => true,
            ]);
        }
    }
}
