<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['OPS-ASSOC','Operations Associate','09','L1','Worker',  1, 15000, 35000],
            ['MECH',     'Mechanic',            '08','L1','Worker',  2, 18000, 38000],
            ['PLUMB',    'Plumber',             '08','L1','Worker',  2, 16000, 32000],
            ['GARD',     'Gardener',            '09','L1','Worker',  4, 12000, 22000],
            ['SECG',     'Security Guard',      '09','L1','Worker',  3, 12000, 22000],
            ['LAB',      'Labour',              '09','L1','Worker',  1, 11000, 18000],
            ['STK',      'Store Keeper',        '08','L2','Sub-Staff',6, 22000, 40000],
            ['EXC-EXEC', 'Excise Executive',    '06','L3','Staff',   7, 32000, 58000],
            ['SR-ACC',   'Sr. Accountant',      '03','L3','Staff',   8, 45000, 90000],
            ['PLANT-MGR','Plant Manager',       '01','L4','Manager', 1, 95000,180000],
            ['AREA-SM',  'Area Sales Manager',  '02','L4','Manager',10, 75000,150000],
            ['CHRO',     'Chief Human Resources Officer','C','L6','Director',9,280000,600000],
        ];
        foreach ($rows as [$code,$name,$grade,$level,$band,$dept,$min,$max]) {
            Designation::create([
                'designation_code'  => $code,
                'designation_name'  => $name,
                'grade'             => $grade,
                'level'             => $level,
                'band'              => $band,
                'dept_id'           => $dept,
                'min_gross'         => $min,
                'max_gross'         => $max,
                'active_flag'       => true,
            ]);
        }
    }
}
