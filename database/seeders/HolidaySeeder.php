<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

/**
 * Seeds India FY 2025-26 + FY 2026-27 holidays (national + major regional).
 */
class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            // FY 2025-26 (1-Apr-2025 to 31-Mar-2026)
            ['2025-04-06', "Ram Navami",              'Festival', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-04-10', "Mahavir Jayanti",         'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-04-14', "Dr. Ambedkar Jayanti",    'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-04-18', "Good Friday",             'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-05-01', "Maharashtra/Labour Day",  'Regional', 'MH,KA,TN,KL',   'ALL', false, '2025-26'],
            ['2025-05-12', "Buddha Purnima",          'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-06-07', "Bakrid / Eid-ul-Adha",    'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-08-15', "Independence Day",        'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-08-16', "Janmashtami",             'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-08-27', "Ganesh Chaturthi",        'Regional', 'MH,GA,KA,TN,AP','ALL', false, '2025-26'],
            ['2025-10-02', "Gandhi Jayanti",          'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-10-20', "Diwali (Lakshmi Pujan)",  'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-10-22', "Bhai Dooj",               'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-11-05', "Guru Nanak Jayanti",      'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2025-12-25', "Christmas",               'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2026-01-26', "Republic Day",            'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2026-03-04', "Holi",                    'National', 'Pan-India',     'ALL', false, '2025-26'],
            ['2026-03-21', "Eid-ul-Fitr",             'National', 'Pan-India',     'ALL', false, '2025-26'],

            // FY 2026-27 starter set
            ['2026-04-14', "Dr. Ambedkar Jayanti",    'National', 'Pan-India',     'ALL', false, '2026-27'],
            ['2026-04-26', "Ram Navami",              'Festival', 'Pan-India',     'ALL', false, '2026-27'],
            ['2026-08-15', "Independence Day",        'National', 'Pan-India',     'ALL', false, '2026-27'],
            ['2026-10-02', "Gandhi Jayanti",          'National', 'Pan-India',     'ALL', false, '2026-27'],
            ['2026-11-08', "Diwali",                  'National', 'Pan-India',     'ALL', false, '2026-27'],
            ['2026-12-25', "Christmas",               'National', 'Pan-India',     'ALL', false, '2026-27'],
            ['2027-01-26', "Republic Day",            'National', 'Pan-India',     'ALL', false, '2026-27'],
        ];

        foreach ($holidays as [$date, $name, $type, $states, $locs, $optional, $fy]) {
            Holiday::updateOrCreate(
                ['holiday_date' => $date, 'holiday_name' => $name],
                [
                    'holiday_type'         => $type,
                    'applicable_states'    => $states,
                    'applicable_locations' => $locs,
                    'optional_flag'        => $optional,
                    'fy_year'              => $fy,
                    'declared_by'          => 'GoI Gazette',
                    'active_flag'          => true,
                ]
            );
        }
        $this->command->info('Seeded ' . count($holidays) . ' holidays.');
    }
}
