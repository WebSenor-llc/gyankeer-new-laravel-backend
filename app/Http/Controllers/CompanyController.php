<?php

namespace App\Http\Controllers;

use App\Models\Company;

class CompanyController extends CrudController
{
    protected string $modelClass = Company::class;
    protected string $title      = 'Manage Companies';
    protected string $singular   = 'Company';
    protected string $routeBase  = 'companies';

    protected array $listColumns = [
        'company_code' => 'Code',
        'company_name' => 'Company Name',
        'pan'          => 'PAN',
        'gstin'        => 'GSTIN',
        'city'         => 'City',
        'active_flag'  => 'Active',
    ];

    protected array $searchable = ['company_code', 'company_name', 'pan', 'gstin', 'cin'];

    protected function fields(): array
    {
        return [
            ['name' => 'company_code',  'label' => 'Company Code', 'type' => 'text', 'required' => true],
            ['name' => 'company_name',  'label' => 'Company Name', 'type' => 'text', 'required' => true],
            ['name' => 'legal_name',    'label' => 'Legal Name',   'type' => 'text', 'col' => 2],
            ['name' => 'entity_type',   'label' => 'Entity Type',  'type' => 'select', 'options' => [
                'Private Limited' => 'Private Limited',
                'Public Limited'  => 'Public Limited',
                'LLP'             => 'LLP',
                'Partnership'     => 'Partnership',
                'Proprietorship'  => 'Proprietorship',
                'Trust'           => 'Trust',
                'Society'         => 'Society',
            ]],
            ['name' => 'sector',        'label' => 'Sector', 'type' => 'text'],
            ['name' => 'cin',           'label' => 'CIN',    'type' => 'text'],
            ['name' => 'pan',           'label' => 'PAN',    'type' => 'text', 'help' => '10-character PAN'],
            ['name' => 'tan',           'label' => 'TAN',    'type' => 'text'],
            ['name' => 'gstin',         'label' => 'GSTIN',  'type' => 'text', 'help' => '15-character GSTIN'],
            ['name' => 'epf_establishment_code', 'label' => 'EPF Establishment Code', 'type' => 'text'],
            ['name' => 'esic_code',     'label' => 'ESIC Code',                       'type' => 'text'],
            ['name' => 'incorporation_date', 'label' => 'Incorporation Date', 'type' => 'date'],
            ['name' => 'fy_start_month','label' => 'FY Start Month', 'type' => 'number', 'help' => '1-12 (e.g. 4 for April)'],
            ['name' => 'registered_address_line1', 'label' => 'Registered Address — Line 1', 'type' => 'text', 'col' => 2],
            ['name' => 'registered_address_line2', 'label' => 'Registered Address — Line 2', 'type' => 'text', 'col' => 2],
            ['name' => 'city',          'label' => 'City',     'type' => 'text'],
            ['name' => 'state',         'label' => 'State',    'type' => 'text'],
            ['name' => 'pin_code',      'label' => 'PIN Code', 'type' => 'text'],
            ['name' => 'country',       'label' => 'Country',  'type' => 'text'],
            ['name' => 'phone',         'label' => 'Phone',    'type' => 'text'],
            ['name' => 'email',         'label' => 'Email',    'type' => 'email'],
            ['name' => 'website',       'label' => 'Website',  'type' => 'text'],
            ['name' => 'hr_contact_email', 'label' => 'HR Contact Email', 'type' => 'email'],
            ['name' => 'status',        'label' => 'Status', 'type' => 'select', 'options' => [
                'Active'   => 'Active',
                'Inactive' => 'Inactive',
                'Closed'   => 'Closed',
            ]],
            ['name' => 'active_flag',   'label' => 'Active', 'type' => 'boolean'],
        ];
    }
}
