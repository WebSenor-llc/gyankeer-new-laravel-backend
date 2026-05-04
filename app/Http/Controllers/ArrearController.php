<?php

namespace App\Http\Controllers;

use App\Models\Arrear;

class ArrearController extends CrudController
{
    protected string $modelClass = Arrear::class;
    protected string $title      = 'Arrears';
    protected string $singular   = 'Arrear';
    protected string $routeBase  = 'arrears';

    protected array $listColumns = [
        'employee_name'           => 'Employee',
        'arrear_type'             => 'Type',
        'posting_period_year'     => 'Posting Year',
        'posting_period_month'    => 'Posting Month',
        'months'                  => 'Months',
        'total_arrear'            => 'Total (₹)',
    ];

    protected array $searchable = ['employee_name', 'arrear_type'];
    protected ?string $companyScope = 'company_id';

    protected function fields(): array
    {
        return [
            ['name' => 'emp_id',                'label' => 'Employee ID', 'type' => 'number'],
            ['name' => 'employee_name',         'label' => 'Employee Name', 'type' => 'text', 'required' => true],
            ['name' => 'arrear_type',           'label' => 'Arrear Type', 'type' => 'select', 'options' => [
                'Salary Revision' => 'Salary Revision',
                'Promotion'       => 'Promotion',
                'DA Revision'     => 'DA Revision',
                'Wage Settlement' => 'Wage Settlement',
                'Bonus Arrear'    => 'Bonus Arrear',
                'Other'           => 'Other',
            ]],
            ['name' => 'from_period_year',      'label' => 'From Year',  'type' => 'number'],
            ['name' => 'from_period_month',     'label' => 'From Month', 'type' => 'number', 'help' => '1-12'],
            ['name' => 'to_period_year',        'label' => 'To Year',    'type' => 'number'],
            ['name' => 'to_period_month',       'label' => 'To Month',   'type' => 'number', 'help' => '1-12'],
            ['name' => 'posting_period_year',   'label' => 'Posting Year',  'type' => 'number'],
            ['name' => 'posting_period_month',  'label' => 'Posting Month', 'type' => 'number', 'help' => '1-12'],
            ['name' => 'months',                'label' => 'Total Months', 'type' => 'number'],
            ['name' => 'old_gross',             'label' => 'Old Gross (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'new_gross',             'label' => 'New Gross (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'total_arrear',          'label' => 'Total Arrear (₹)', 'type' => 'number', 'step' => '0.01', 'required' => true],
            ['name' => 'old_basic',             'label' => 'Old Basic (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'new_basic',             'label' => 'New Basic (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'pf_diff_emp',           'label' => 'PF Diff (Employee)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'pf_diff_er',            'label' => 'PF Diff (Employer)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'eps_diff',              'label' => 'EPS Diff', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'esi_diff_emp',          'label' => 'ESI Diff (Employee)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'esi_diff_er',           'label' => 'ESI Diff (Employer)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'tds_arrear',            'label' => 'TDS Arrear (₹)', 'type' => 'number', 'step' => '0.01'],
        ];
    }
}
