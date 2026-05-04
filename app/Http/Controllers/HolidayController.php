<?php

namespace App\Http\Controllers;

use App\Models\Holiday;

class HolidayController extends CrudController
{
    protected string $modelClass = Holiday::class;
    protected string $title      = 'Holidays';
    protected string $singular   = 'Holiday';
    protected string $routeBase  = 'holidays';

    protected array $listColumns = [
        'holiday_date'         => 'Date',
        'holiday_name'         => 'Name',
        'holiday_type'         => 'Type',
        'fy_year'              => 'FY',
        'optional_flag'        => 'Optional',
        'active_flag'          => 'Active',
    ];

    protected array $searchable = ['holiday_name', 'holiday_type', 'fy_year'];

    protected function fields(): array
    {
        return [
            ['name' => 'holiday_date',         'label' => 'Date', 'type' => 'date', 'required' => true],
            ['name' => 'holiday_name',         'label' => 'Holiday Name', 'type' => 'text', 'required' => true, 'col' => 2],
            ['name' => 'holiday_type',         'label' => 'Type', 'type' => 'select', 'options' => [
                'National'      => 'National',
                'Festival'      => 'Festival',
                'Regional'      => 'Regional',
                'Optional'      => 'Optional',
                'Restricted'    => 'Restricted',
                'Floating'      => 'Floating',
            ]],
            ['name' => 'fy_year',              'label' => 'Financial Year', 'type' => 'text', 'help' => 'e.g. 2025-26'],
            ['name' => 'applicable_states',    'label' => 'Applicable States', 'type' => 'text', 'col' => 2, 'help' => 'Comma-separated state names'],
            ['name' => 'applicable_locations', 'label' => 'Applicable Locations', 'type' => 'text', 'col' => 2],
            ['name' => 'declared_by',          'label' => 'Declared By', 'type' => 'text'],
            ['name' => 'gazette_ref',          'label' => 'Gazette Reference', 'type' => 'text'],
            ['name' => 'remarks',              'label' => 'Remarks', 'type' => 'textarea', 'col' => 2],
            ['name' => 'optional_flag',        'label' => 'Optional Holiday', 'type' => 'boolean'],
            ['name' => 'active_flag',          'label' => 'Active', 'type' => 'boolean'],
        ];
    }
}
