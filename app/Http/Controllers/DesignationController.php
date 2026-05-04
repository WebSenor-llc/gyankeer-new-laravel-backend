<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Models\Department;

class DesignationController extends CrudController
{
    protected string $modelClass = Designation::class;
    protected string $title      = 'Manage Designations';
    protected string $singular   = 'Designation';
    protected string $routeBase  = 'designations';

    protected array $listColumns = [
        'designation_code' => 'Code',
        'designation_name' => 'Designation',
        'grade'            => 'Grade',
        'level'            => 'Level',
        'band'             => 'Band',
        'people_manager_flag' => 'Manager',
        'active_flag'      => 'Active',
    ];

    protected array $searchable = ['designation_code', 'designation_name', 'grade', 'level', 'band', 'job_family'];

    protected function fields(): array
    {
        $depts = Department::pluck('dept_name', 'dept_id')->toArray();

        return [
            ['name' => 'designation_code', 'label' => 'Code', 'type' => 'text', 'required' => true],
            ['name' => 'designation_name', 'label' => 'Designation Name', 'type' => 'text', 'required' => true],
            ['name' => 'dept_id',          'label' => 'Department', 'type' => 'select', 'options' => $depts],
            ['name' => 'grade',            'label' => 'Grade', 'type' => 'text'],
            ['name' => 'level',            'label' => 'Level', 'type' => 'text'],
            ['name' => 'band',             'label' => 'Band',  'type' => 'text'],
            ['name' => 'job_family',       'label' => 'Job Family', 'type' => 'text'],
            ['name' => 'job_function',     'label' => 'Job Function', 'type' => 'text'],
            ['name' => 'job_category',     'label' => 'Job Category', 'type' => 'text'],
            ['name' => 'min_gross',        'label' => 'Min Gross (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'max_gross',        'label' => 'Max Gross (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'min_basic',        'label' => 'Min Basic (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'exempt_from_overtime', 'label' => 'Exempt from Overtime', 'type' => 'select', 'options' => ['Yes' => 'Yes', 'No' => 'No']],
            ['name' => 'people_manager_flag', 'label' => 'People Manager', 'type' => 'boolean'],
            ['name' => 'apprentice_flag',     'label' => 'Apprentice',     'type' => 'boolean'],
            ['name' => 'contract_flag',       'label' => 'Contract Role',  'type' => 'boolean'],
            ['name' => 'effective_from',      'label' => 'Effective From', 'type' => 'date'],
            ['name' => 'active_flag',         'label' => 'Active',         'type' => 'boolean'],
        ];
    }
}
