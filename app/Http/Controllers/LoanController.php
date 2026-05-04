<?php

namespace App\Http\Controllers;

use App\Models\LoanAdvance;

class LoanController extends CrudController
{
    protected string $modelClass = LoanAdvance::class;
    protected string $title      = 'Loans & Advances';
    protected string $singular   = 'Loan';
    protected string $routeBase  = 'loans';

    protected array $listColumns = [
        'employee_name'         => 'Employee',
        'loan_type'             => 'Type',
        'principal'             => 'Principal (₹)',
        'emi_amount'            => 'EMI (₹)',
        'outstanding_principal' => 'Outstanding (₹)',
        'repayment_status'      => 'Status',
        'active_flag'           => 'Active',
    ];

    protected array $searchable = ['employee_name', 'loan_type', 'repayment_status'];
    protected ?string $companyScope = 'company_id';

    protected function fields(): array
    {
        return [
            ['name' => 'emp_id',           'label' => 'Employee ID',     'type' => 'number'],
            ['name' => 'employee_name',    'label' => 'Employee Name',   'type' => 'text', 'required' => true],
            ['name' => 'loan_type',        'label' => 'Loan Type', 'type' => 'select', 'options' => [
                'Personal Loan'    => 'Personal Loan',
                'Salary Advance'   => 'Salary Advance',
                'Festival Advance' => 'Festival Advance',
                'Vehicle Loan'     => 'Vehicle Loan',
                'Housing Loan'     => 'Housing Loan',
                'Education Loan'   => 'Education Loan',
                'Medical Advance'  => 'Medical Advance',
                'Other'            => 'Other',
            ]],
            ['name' => 'principal',        'label' => 'Principal Amount (₹)', 'type' => 'number', 'step' => '0.01', 'required' => true],
            ['name' => 'interest_rate_pct','label' => 'Interest Rate (%)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'tenure_months',    'label' => 'Tenure (months)', 'type' => 'number'],
            ['name' => 'emi_amount',       'label' => 'EMI Amount (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'start_date',       'label' => 'Start Date', 'type' => 'date'],
            ['name' => 'end_date',         'label' => 'End Date',   'type' => 'date'],
            ['name' => 'outstanding_principal', 'label' => 'Outstanding Principal (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'outstanding_interest',  'label' => 'Outstanding Interest (₹)',  'type' => 'number', 'step' => '0.01'],
            ['name' => 'sanction_date',    'label' => 'Sanction Date', 'type' => 'date'],
            ['name' => 'sanction_doc',     'label' => 'Sanction Document Reference', 'type' => 'text'],
            ['name' => 'approver_name',    'label' => 'Approver Name', 'type' => 'text'],
            ['name' => 'repayment_status', 'label' => 'Repayment Status', 'type' => 'select', 'options' => [
                'Active'    => 'Active',
                'Closed'    => 'Closed',
                'Defaulted' => 'Defaulted',
                'Hold'      => 'On Hold',
            ]],
            ['name' => 'last_emi_paid_date', 'label' => 'Last EMI Paid Date', 'type' => 'date'],
            ['name' => 'total_paid',       'label' => 'Total Paid (₹)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'perq_section',     'label' => 'Perquisite IT Section', 'type' => 'text', 'help' => 'For interest-free / concessional loans'],
            ['name' => 'remarks',          'label' => 'Remarks', 'type' => 'textarea', 'col' => 2],
            ['name' => 'active_flag',      'label' => 'Active', 'type' => 'boolean'],
        ];
    }
}
