<?php

namespace App\Http\Controllers;

use App\Models\SalaryGroup;
use App\Models\Company;
use Illuminate\Http\Request;

class SalaryGroupController extends CrudController
{
    protected string $modelClass = SalaryGroup::class;
    protected string $title      = 'Salary Groups';
    protected string $singular   = 'Salary Group';
    protected string $routeBase  = 'salary-groups';

    protected array $listColumns = [
        'salary_group_name'   => 'Salary Group',
        'group_type'          => 'Type',
        'employees_count'     => 'Employees',
        'wage_periodicity'    => 'Periodicity',
        'pf_applicable'       => 'PF',
        'esi_applicable'      => 'ESI',
        'pt_applicable'       => 'PT',
        'status'              => 'Status',
    ];

    protected array $searchable = ['salary_group_name', 'group_type', 'min_wage_state'];
    protected ?string $companyScope = 'company_id';

    /**
     * Override index to attach `employees_count` per group so the data
     * table shows live headcount alongside the group name.
     */
    public function index(Request $req)
    {
        $query = SalaryGroup::withCount('employees');

        if ($this->companyScope) {
            $cid = (int) session('active_company_id', 0);
            if ($cid) {
                $query->where($this->companyScope, $cid);
            }
        }

        if ($req->filled('q') && !empty($this->searchable)) {
            $q = $req->q;
            $query->where(function ($w) use ($q) {
                foreach ($this->searchable as $col) {
                    $w->orWhere($col, 'like', "%$q%");
                }
            });
        }

        $records = $query->orderBy('salary_group_name')->paginate(50)->appends($req->query());

        return view('crud.index', [
            'records'      => $records,
            'title'        => $this->title,
            'singular'     => $this->singular,
            'routeBase'    => $this->routeBase,
            'listColumns'  => $this->listColumns,
            'searchable'   => $this->searchable,
            'searchQuery'  => $req->q,
            'pkName'       => 'salary_group_id',
        ]);
    }

    protected function fields(): array
    {
        $companies = Company::pluck('company_name', 'company_id')->toArray();

        return [
            ['name' => 'salary_group_name', 'label' => 'Group Name', 'type' => 'text', 'required' => true],
            ['name' => 'group_type',        'label' => 'Group Type', 'type' => 'select', 'options' => [
                'Staff'     => 'Staff',
                'Sub-Staff' => 'Sub-Staff',
                'Worker'    => 'Worker',
                'Contract'  => 'Contract',
                'Trainee'   => 'Trainee',
            ]],
            ['name' => 'company_id',     'label' => 'Company', 'type' => 'select', 'options' => $companies],
            ['name' => 'bonus_per',      'label' => 'Bonus %', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'min_wage_state', 'label' => 'Min Wage State', 'type' => 'text'],
            ['name' => 'wage_periodicity', 'label' => 'Wage Periodicity', 'type' => 'select', 'options' => [
                'Monthly'    => 'Monthly',
                'Daily'      => 'Daily',
                'Hourly'     => 'Hourly',
                'Piece-rate' => 'Piece-rate',
            ]],
            ['name' => 'pf_applicable',       'label' => 'PF Applicable',       'type' => 'boolean'],
            ['name' => 'esi_applicable',      'label' => 'ESI Applicable',      'type' => 'boolean'],
            ['name' => 'pt_applicable',       'label' => 'PT Applicable',       'type' => 'boolean'],
            ['name' => 'lwf_applicable',      'label' => 'LWF Applicable',      'type' => 'boolean'],
            ['name' => 'gratuity_applicable', 'label' => 'Gratuity Applicable', 'type' => 'boolean'],
            ['name' => 'overtime_eligible',   'label' => 'Overtime Eligible',   'type' => 'boolean'],
            ['name' => 'effective_from',      'label' => 'Effective From', 'type' => 'date'],
            ['name' => 'effective_to',        'label' => 'Effective To',   'type' => 'date'],
            ['name' => 'status',              'label' => 'Status', 'type' => 'select', 'options' => [
                'Active'   => 'Active',
                'Inactive' => 'Inactive',
            ]],
        ];
    }
}
