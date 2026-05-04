<?php

namespace App\Http\Controllers;

use App\Models\SalaryComponent;

class SalaryComponentController extends CrudController
{
    protected string $modelClass = SalaryComponent::class;
    protected string $title      = 'Salary Components';
    protected string $singular   = 'Component';
    protected string $routeBase  = 'salary-components';

    protected array $listColumns = [
        'component_code' => 'Code',
        'component_name' => 'Component',
        'component_type' => 'Type',
        'is_taxable'     => 'Taxable',
        'pf_wage'        => 'PF Wage',
        'esi_wage'       => 'ESI Wage',
        'show_on_payslip'=> 'On Payslip',
        'status'         => 'Status',
    ];

    protected array $searchable = ['component_code', 'component_name', 'component_type'];

    protected function fields(): array
    {
        return [
            ['name' => 'component_code', 'label' => 'Component Code', 'type' => 'text', 'required' => true],
            ['name' => 'component_name', 'label' => 'Component Name', 'type' => 'text', 'required' => true],
            ['name' => 'component_type', 'label' => 'Type', 'type' => 'select', 'options' => [
                'Earning'    => 'Earning',
                'Deduction'  => 'Deduction',
                'Reimbursement' => 'Reimbursement',
                'Statutory'  => 'Statutory',
                'Employer-Contribution' => 'Employer Contribution',
            ], 'required' => true],
            ['name' => 'calculation_type', 'label' => 'Calculation Type', 'type' => 'select', 'options' => [
                'Fixed'      => 'Fixed Amount',
                'Percentage' => 'Percentage of Base',
                'Formula'    => 'Custom Formula',
                'Slab'       => 'Slab-based',
            ]],
            ['name' => 'percentage_base', 'label' => 'Percentage of Base (%)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'fixed_amount',    'label' => 'Fixed Amount (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'formula',         'label' => 'Formula', 'type' => 'textarea', 'col' => 2],
            ['name' => 'exemption_section', 'label' => 'Exemption Section', 'type' => 'text', 'help' => 'IT Section, e.g. 10(13A) for HRA'],
            ['name' => 'gl_account_code', 'label' => 'GL Account Code', 'type' => 'text'],
            ['name' => 'sequence_order',  'label' => 'Sequence Order', 'type' => 'number'],
            ['name' => 'effective_from',  'label' => 'Effective From', 'type' => 'date'],
            ['name' => 'effective_to',    'label' => 'Effective To',   'type' => 'date'],
            ['name' => 'is_taxable',      'label' => 'Is Taxable',          'type' => 'boolean'],
            ['name' => 'taxable_under_old', 'label' => 'Taxable (Old Regime)', 'type' => 'boolean'],
            ['name' => 'taxable_under_new', 'label' => 'Taxable (New Regime)', 'type' => 'boolean'],
            ['name' => 'partial_exemption_rule', 'label' => 'Partial Exemption Rule', 'type' => 'boolean'],
            ['name' => 'pf_wage',         'label' => 'PF Wage Component',     'type' => 'boolean'],
            ['name' => 'esi_wage',        'label' => 'ESI Wage Component',    'type' => 'boolean'],
            ['name' => 'pt_wage',         'label' => 'PT Wage Component',     'type' => 'boolean'],
            ['name' => 'gratuity_wage',   'label' => 'Gratuity Wage',         'type' => 'boolean'],
            ['name' => 'bonus_wage',      'label' => 'Bonus Wage',            'type' => 'boolean'],
            ['name' => 'show_on_payslip', 'label' => 'Show on Payslip',       'type' => 'boolean'],
            ['name' => 'statutory_flag',  'label' => 'Statutory Component',   'type' => 'boolean'],
            ['name' => 'status',          'label' => 'Status', 'type' => 'select', 'options' => [
                'Active'   => 'Active',
                'Inactive' => 'Inactive',
            ]],
        ];
    }
}
