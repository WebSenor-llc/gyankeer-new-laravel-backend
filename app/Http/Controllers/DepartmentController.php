<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Company;

class DepartmentController extends CrudController
{
    protected string $modelClass = Department::class;
    protected string $title      = 'Manage Departments';
    protected string $singular   = 'Department';
    protected string $routeBase  = 'departments';

    protected array $listColumns = [
        'dept_code'           => 'Code',
        'dept_name'           => 'Department',
        'business_unit'       => 'Business Unit',
        'budgeted_headcount'  => 'Budgeted',
        'actual_headcount'    => 'Actual',
        'active_flag'         => 'Active',
    ];

    protected array $searchable = ['dept_code', 'dept_name', 'business_unit', 'cost_center_code'];
    protected ?string $companyScope = 'company_id';

    protected function fields(): array
    {
        $companies = Company::pluck('company_name', 'company_id')->toArray();

        return [
            ['name' => 'dept_code',       'label' => 'Department Code', 'type' => 'text', 'required' => true],
            ['name' => 'dept_name',       'label' => 'Department Name', 'type' => 'text', 'required' => true],
            ['name' => 'company_id',      'label' => 'Company', 'type' => 'select', 'options' => $companies],
            ['name' => 'business_unit',   'label' => 'Business Unit', 'type' => 'text'],
            ['name' => 'cost_center_code','label' => 'Cost Center Code', 'type' => 'text'],
            ['name' => 'gl_expense_account', 'label' => 'GL Expense Account', 'type' => 'text'],
            ['name' => 'budgeted_headcount', 'label' => 'Budgeted Headcount', 'type' => 'number'],
            ['name' => 'actual_headcount',   'label' => 'Actual Headcount',   'type' => 'number'],
            ['name' => 'attrition_target_pct', 'label' => 'Attrition Target %', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'effective_from',  'label' => 'Effective From', 'type' => 'date'],
            ['name' => 'active_flag',     'label' => 'Active', 'type' => 'boolean'],
        ];
    }
}
