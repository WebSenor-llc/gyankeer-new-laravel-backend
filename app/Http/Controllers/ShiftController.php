<?php

namespace App\Http\Controllers;

use App\Models\Shift;

class ShiftController extends CrudController
{
    protected string $modelClass = Shift::class;
    protected string $title      = 'Shifts';
    protected string $singular   = 'Shift';
    protected string $routeBase  = 'shifts';

    protected array $listColumns = [
        'shift_code'        => 'Code',
        'shift_name'        => 'Shift',
        'start_time'        => 'Start',
        'end_time'          => 'End',
        'total_hours'       => 'Hours',
        'night_shift_flag'  => 'Night',
        'active_flag'       => 'Active',
    ];

    protected array $searchable = ['shift_code', 'shift_name'];

    protected function fields(): array
    {
        return [
            ['name' => 'shift_code',  'label' => 'Shift Code', 'type' => 'text', 'required' => true],
            ['name' => 'shift_name',  'label' => 'Shift Name', 'type' => 'text', 'required' => true],
            ['name' => 'start_time',  'label' => 'Start Time', 'type' => 'time'],
            ['name' => 'end_time',    'label' => 'End Time',   'type' => 'time'],
            ['name' => 'total_hours', 'label' => 'Total Hours', 'type' => 'number', 'step' => '0.25'],
            ['name' => 'break_minutes','label' => 'Break Minutes', 'type' => 'text'],
            ['name' => 'net_hours',   'label' => 'Net Hours', 'type' => 'text'],
            ['name' => 'weekly_pattern', 'label' => 'Weekly Pattern', 'type' => 'text', 'help' => 'e.g. M-F, M-Sat'],
            ['name' => 'weekly_off_days', 'label' => 'Weekly Off Days', 'type' => 'number', 'step' => '0.5'],
            ['name' => 'applicable_locations', 'label' => 'Applicable Locations', 'type' => 'text', 'col' => 2],
            ['name' => 'applicable_genders',   'label' => 'Applicable Genders',   'type' => 'select', 'options' => [
                'All'            => 'All',
                'Male'           => 'Male only',
                'Female'         => 'Female only',
                'Female-consent' => 'Female (with consent)',
            ]],
            ['name' => 'grace_minutes_late',     'label' => 'Grace (Late)',      'type' => 'text'],
            ['name' => 'grace_minutes_early_out','label' => 'Grace (Early Out)', 'type' => 'text'],
            ['name' => 'min_hours_for_full_day', 'label' => 'Min Hours for Full Day', 'type' => 'text'],
            ['name' => 'max_ot_hours_per_qtr',   'label' => 'Max OT Hours / Quarter', 'type' => 'number'],
            ['name' => 'color_code',          'label' => 'Color Code', 'type' => 'text', 'help' => 'e.g. #2563EB'],
            ['name' => 'night_shift_flag',    'label' => 'Night Shift',          'type' => 'boolean'],
            ['name' => 'female_with_consent_required', 'label' => 'Female Consent Required', 'type' => 'boolean'],
            ['name' => 'transport_provided',  'label' => 'Transport Provided',   'type' => 'boolean'],
            ['name' => 'ot_eligible',         'label' => 'OT Eligible',          'type' => 'boolean'],
            ['name' => 'attendance_required', 'label' => 'Attendance Required',  'type' => 'boolean'],
            ['name' => 'gps_clock_in_allowed','label' => 'GPS Clock-in Allowed', 'type' => 'boolean'],
            ['name' => 'active_flag',         'label' => 'Active',               'type' => 'boolean'],
        ];
    }
}
