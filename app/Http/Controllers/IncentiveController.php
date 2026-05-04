<?php

namespace App\Http\Controllers;

use App\Models\Incentive;

class IncentiveController extends CrudController
{
    protected string $modelClass = Incentive::class;
    protected string $title      = 'Incentives';
    protected string $singular   = 'Incentive';
    protected string $routeBase  = 'incentives';

    protected array $listColumns = [
        'employee_name'  => 'Employee',
        'period_year'    => 'Year',
        'period_month'   => 'Month',
        'type'           => 'Type',
        'final_amount'   => 'Amount (₹)',
        'taxable'        => 'Taxable',
    ];

    protected array $searchable = ['employee_name', 'type', 'sub_type', 'linked_kpi'];
    protected ?string $companyScope = 'company_id';

    protected function fields(): array
    {
        return [
            ['name' => 'emp_id',         'label' => 'Employee ID', 'type' => 'number'],
            ['name' => 'employee_name',  'label' => 'Employee Name', 'type' => 'text', 'required' => true],
            ['name' => 'period_year',    'label' => 'Period Year', 'type' => 'number', 'required' => true],
            ['name' => 'period_month',   'label' => 'Period Month', 'type' => 'number', 'help' => '1-12'],
            ['name' => 'type',           'label' => 'Type', 'type' => 'select', 'options' => [
                'Performance' => 'Performance Bonus',
                'Sales'       => 'Sales Incentive',
                'Spot'        => 'Spot Award',
                'Referral'    => 'Referral Bonus',
                'Retention'   => 'Retention Bonus',
                'Joining'     => 'Joining Bonus',
                'Festival'    => 'Festival Bonus',
                'Statutory'   => 'Statutory Bonus',
            ]],
            ['name' => 'sub_type',       'label' => 'Sub-Type', 'type' => 'text'],
            ['name' => 'linked_kpi',     'label' => 'Linked KPI', 'type' => 'text'],
            ['name' => 'reason',         'label' => 'Reason', 'type' => 'textarea', 'col' => 2],
            ['name' => 'slab',           'label' => 'Slab', 'type' => 'text'],
            ['name' => 'target',         'label' => 'Target', 'type' => 'text'],
            ['name' => 'achieved',       'label' => 'Achieved', 'type' => 'text'],
            ['name' => 'achievement_pct','label' => 'Achievement %', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'base_amount',    'label' => 'Base Amount (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'multiplier',     'label' => 'Multiplier', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'final_amount',   'label' => 'Final Amount (₹)', 'type' => 'number', 'step' => '0.01', 'required' => true],
            ['name' => 'approver_name',  'label' => 'Approver Name', 'type' => 'text'],
            ['name' => 'approval_date',  'label' => 'Approval Date', 'type' => 'date'],
            ['name' => 'taxable',        'label' => 'Taxable', 'type' => 'boolean'],
            ['name' => 'pf_wage_flag',   'label' => 'PF Wage', 'type' => 'boolean'],
            ['name' => 'esi_wage_flag',  'label' => 'ESI Wage', 'type' => 'boolean'],
        ];
    }
}
