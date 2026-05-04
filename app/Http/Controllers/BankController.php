<?php

namespace App\Http\Controllers;

use App\Models\Bank;

class BankController extends CrudController
{
    protected string $modelClass = Bank::class;
    protected string $title      = 'Manage Banks';
    protected string $singular   = 'Bank';
    protected string $routeBase  = 'banks';

    protected array $listColumns = [
        'bank_code'          => 'Code',
        'bank_name'          => 'Bank',
        'branch_name'        => 'Branch',
        'ifsc_master'        => 'IFSC',
        'default_bank_flag'  => 'Default',
        'active_flag'        => 'Active',
    ];

    protected array $searchable = ['bank_code', 'bank_name', 'ifsc_master', 'branch_name', 'micr_code'];

    protected function fields(): array
    {
        return [
            ['name' => 'bank_code',     'label' => 'Bank Code', 'type' => 'text', 'required' => true],
            ['name' => 'bank_name',     'label' => 'Bank Name', 'type' => 'text', 'required' => true],
            ['name' => 'ifsc_master',   'label' => 'IFSC',      'type' => 'text', 'help' => '11-character IFSC'],
            ['name' => 'branch_name',   'label' => 'Branch Name', 'type' => 'text'],
            ['name' => 'branch_address','label' => 'Branch Address', 'type' => 'textarea', 'col' => 2],
            ['name' => 'branch_city',   'label' => 'Branch City',  'type' => 'text'],
            ['name' => 'branch_state',  'label' => 'Branch State', 'type' => 'text'],
            ['name' => 'branch_pin',    'label' => 'Branch PIN',   'type' => 'text'],
            ['name' => 'micr_code',     'label' => 'MICR Code',    'type' => 'text', 'help' => '9-digit MICR'],
            ['name' => 'swift_code',    'label' => 'SWIFT Code',   'type' => 'text'],
            ['name' => 'salary_account_no',      'label' => 'Salary Account No.',      'type' => 'text'],
            ['name' => 'statutory_account_no',   'label' => 'Statutory Account No.',   'type' => 'text'],
            ['name' => 'disbursement_account_no','label' => 'Disbursement Account No.','type' => 'text'],
            ['name' => 'beneficiary_name',       'label' => 'Beneficiary Name',        'type' => 'text', 'col' => 2],
            ['name' => 'contact_person', 'label' => 'Contact Person', 'type' => 'text'],
            ['name' => 'contact_phone',  'label' => 'Contact Phone',  'type' => 'text'],
            ['name' => 'contact_email',  'label' => 'Contact Email',  'type' => 'email'],
            ['name' => 'default_bank_flag','label' => 'Default Salary Bank', 'type' => 'boolean'],
            ['name' => 'active_flag',      'label' => 'Active',              'type' => 'boolean'],
        ];
    }
}
