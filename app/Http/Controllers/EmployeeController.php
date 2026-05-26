<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryGroup;
use Illuminate\Http\Request;

/**
 * Employee Controller — handles the Manage Employee module.
 *
 * Real implementations: index, show + 8 profile tabs (education, employment,
 * statutory, bank, documents, family, career, salary).
 */
class EmployeeController extends StubController
{
    protected string $title = 'Manage Employee';

    public function index(Request $req)
    {
        try {
            $query = Employee::with(['department', 'designation', 'salary_group']);

            // Active-company scope from session (header dropdown)
            $cid = (int) session('active_company_id', 0);
            if ($cid) {
                $query->where('company_id', $cid);
            }

            if ($req->filled('q')) {
                $q = $req->q;
                $query->where(function ($q2) use ($q) {
                    $q2->where('emp_id', 'like', "%$q%")
                        ->orWhere('full_name', 'like', "%$q%")
                        ->orWhere('third_party_code', 'like', "%$q%")
                        ->orWhere('pan_no', 'like', "%$q%")
                        ->orWhere('uan', 'like', "%$q%");
                });
            }
            if ($req->filled('dept_id'))  $query->where('dept_id',           $req->dept_id);
            if ($req->filled('emp_type')) $query->where('employee_type',     $req->emp_type);
            if ($req->filled('status'))   $query->where('employment_status', $req->status);

            return view('employees.index', [
                'employees'    => $query->orderBy('emp_id')->paginate(50),
                'departments'  => Department::when($cid, fn($q) => $q->where('company_id', $cid))->get(),
                'designations' => Designation::all(),
            ]);
        } catch (\Throwable $e) {
            return $this->stub('index — ' . $e->getMessage());
        }
    }

    public function exitList(Request $req)
    {
        $cid = (int) session('active_company_id', 0);
        $q = Employee::with(['department','designation','salary_group'])
            ->whereIn('employment_status', ['Exited','Resigned','Terminated','Retired','Absconded']);
        if ($cid) $q->where('company_id', $cid);
        if ($req->filled('q')) {
            $sq = $req->q;
            $q->where(function ($w) use ($sq) {
                $w->where('full_name', 'like', "%$sq%")->orWhere('emp_id', 'like', "%$sq%");
            });
        }

        return view('employees.exit-list', [
            'employees'    => $q->orderByDesc('updated_at')->paginate(50)->appends($req->query()),
            'departments'  => Department::when($cid, fn($q) => $q->where('company_id', $cid))->get(),
            'totalActive'  => Employee::where('active_flag', true)->where('employment_status','Active')
                ->when($cid, fn($q) => $q->where('company_id', $cid))->count(),
            'totalExited'  => Employee::whereIn('employment_status', ['Exited','Resigned','Terminated','Retired','Absconded'])
                ->when($cid, fn($q) => $q->where('company_id', $cid))->count(),
        ]);
    }

    /**
     * Show the "Mark Employee as Exited" form for an existing employee.
     */
    public function exitForm($empId)
    {
        $emp = Employee::where('emp_id', $empId)->firstOrFail();
        return view('employees.exit-form', compact('emp'));
    }

    /**
     * Show the picker — choose an existing active employee, then take them to
     * the exit form. Linked from the Exit Employees list.
     */
    public function exitPicker(Request $req)
    {
        $cid = (int) session('active_company_id', 0);
        $employees = Employee::with('department')
            ->where('active_flag', true)
            ->where('employment_status', 'Active')
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($req->filled('q'), fn($q) => $q->where(function ($w) use ($req) {
                $w->where('full_name', 'like', "%{$req->q}%")
                  ->orWhere('emp_id', 'like', "%{$req->q}%");
            }))
            ->orderBy('emp_id')->paginate(50)->appends($req->query());
        return view('employees.exit-picker', compact('employees'));
    }

    /**
     * Save exit details — updates the employee with relieving date + reason
     * and sets active_flag = false so payroll engine skips them going forward.
     */
    public function exitStore($empId, Request $req)
    {
        $req->validate([
            'date_of_relieving'  => 'required|date',
            'exit_reason'        => 'required|in:Resigned,Terminated,Retired,Absconded,Death,Contract End',
            'notice_served_flag' => 'nullable|boolean',
            'exit_notes'         => 'nullable|string|max:2000',
        ]);

        $emp = Employee::where('emp_id', $empId)->firstOrFail();

        $statusMap = [
            'Resigned'      => 'Resigned',
            'Terminated'    => 'Exited',
            'Retired'       => 'Exited',
            'Absconded'     => 'Exited',
            'Death'         => 'Exited',
            'Contract End'  => 'Exited',
        ];

        $emp->update([
            'employment_status'  => $statusMap[$req->exit_reason] ?? 'Exited',
            'active_flag'        => false,
            'date_of_relieving'  => $req->date_of_relieving,
            'exit_reason'        => $req->exit_reason,
            'notice_served_flag' => $req->boolean('notice_served_flag'),
            'exit_notes'         => $req->exit_notes,
        ]);

        return redirect()->route('exit-employees')
            ->with('status', "Marked {$emp->full_name} ({$emp->emp_id}) as {$req->exit_reason} effective {$req->date_of_relieving}. They are now excluded from active payroll.");
    }

    /**
     * Re-activate an exited employee (mistake correction).
     */
    public function reactivate($empId)
    {
        $emp = Employee::where('emp_id', $empId)->firstOrFail();
        $emp->update([
            'employment_status'  => 'Active',
            'active_flag'        => true,
            'date_of_relieving'  => null,
            'exit_reason'        => null,
        ]);
        return back()->with('status', "Reactivated {$emp->full_name}. They are now eligible for payroll again.");
    }

    public function show($empId)
    {
        $emp = Employee::with(['company', 'department', 'designation', 'salary_group', 'bank', 'shift', 'location'])
            ->where('emp_id', $empId)
            ->firstOrFail();
        return view('employees.show', ['emp' => $emp, 'tab' => 'profile']);
    }

    public function education($empId) { return $this->employeeTab($empId, 'education'); }
    public function employment($empId){ return $this->employeeTab($empId, 'employment'); }
    public function statutory($empId) { return $this->employeeTab($empId, 'statutory'); }
    public function bank($empId)      { return $this->employeeTab($empId, 'bank'); }
    public function documents($empId) { return $this->employeeTab($empId, 'documents'); }
    public function family($empId)    { return $this->employeeTab($empId, 'family'); }
    public function career($empId)    { return $this->employeeTab($empId, 'career'); }
    public function salary($empId)    { return $this->employeeTab($empId, 'salary'); }

    private function employeeTab($empId, string $tab)
    {
        $emp = Employee::with(['company', 'department', 'designation', 'salary_group', 'bank', 'shift'])
            ->where('emp_id', $empId)
            ->firstOrFail();
        return view('employees.show', ['emp' => $emp, 'tab' => $tab]);
    }

    public function create()
    {
        $cid = (int) session('active_company_id', 0);
        return view('employees.create', [
            'companies'    => \App\Models\Company::orderBy('company_name')->get(),
            'departments'  => Department::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('dept_name')->get(),
            'designations' => Designation::orderBy('designation_name')->get(),
            'salaryGroups' => SalaryGroup::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('salary_group_name')->get(),
            'banks'        => Bank::orderBy('bank_name')->get(),
            'activeCompanyId' => $cid,
        ]);
    }

    public function store(Request $req)
    {
        $req->validate([
            'first_name'      => 'required|string|max:100',
            'company_id'      => 'required|integer',
            'employee_type'   => 'nullable|string|max:50',
            'date_of_joining' => 'nullable|date',
            'dob'             => 'nullable|date',
            'pan_no'          => 'nullable|string|max:10',
            'aadhar_id_no'    => 'nullable|string|max:12',
            'personal_email'  => 'nullable|email',
            'company_email'   => 'nullable|email',
        ]);

        $editable = [
            'first_name','middle_name','last_name','full_name','fathers_name','mothers_name',
            'name_prefix','relation_prefix','relative_name','spouse_name',
            'dob','gender','marital_status','blood_group','nationality',
            'personal_mobile','personal_email','company_email','company_mobile',
            'company_id','dept_id','designation_id','salary_group_id','bank_id',
            'employment_status','employee_type','role','contract_type','grade_category',
            'date_of_joining','probation_period_months','on_probation','cost_center','business_unit',
            'work_place','third_party_code',
            'current_basic','current_hra','current_da','current_conv','current_med',
            'current_spl','current_gross','current_ctc','education_allow',
            'bank_account_no','bank_ifsc','account_holder_name',
            'pan_no','aadhar_id_no','aadhar_enrollment_no','voter_id_no','driving_license_no',
            'uan','epf_member_id','esi_ip_no','tax_regime',
            'pt_state','lwf_state',
            'mailing_address_line1','mailing_address_line2','mailing_city','mailing_state','mailing_pincode','mailing_country',
            'permanent_address_line1','permanent_address_line2','permanent_city','permanent_state','permanent_pincode','permanent_country',
            'same_as_mailing',
            'emergency_contact_name','emergency_contact_relation','emergency_contact_phone',
            'reference_letter_no','offer_letter_no','appointment_letter_no',
        ];

        $data = [];
        foreach ($editable as $key) {
            if ($req->has($key)) {
                $val = $req->input($key);
                $data[$key] = $val === '' ? null : $val;
            }
        }
        // Default flags
        $data['active_flag']   = true;
        $data['employment_status'] = $data['employment_status'] ?? 'Active';
        $data['pt_state']      = $data['pt_state']  ?? 'RJ';
        $data['lwf_state']     = $data['lwf_state'] ?? 'RJ';
        $data['on_probation']  = $req->boolean('on_probation');
        $data['same_as_mailing'] = $req->boolean('same_as_mailing');

        // Auto-derive full_name if blank
        if (empty($data['full_name'])) {
            $data['full_name'] = trim(
                ($data['first_name'] ?? '') . ' ' .
                ($data['middle_name'] ?? '') . ' ' .
                ($data['last_name']  ?? '')
            );
        }

        try {
            $emp = Employee::create($data);
        } catch (\Throwable $ex) {
            \Log::error('Employee create failed: ' . $ex->getMessage(), ['data' => $data]);
            return back()->withInput()->withErrors([
                'create' => 'Failed to create employee: ' . $ex->getMessage(),
            ]);
        }

        return redirect()->route('employees.show', $emp->emp_id)
            ->with('status', "Employee #{$emp->emp_id} ({$emp->full_name}) created successfully.");
    }

    public function edit($empId)
    {
        $emp = Employee::where('emp_id', $empId)->firstOrFail();
        return view('employees.edit', [
            'emp'           => $emp,
            'departments'   => Department::orderBy('dept_name')->get(),
            'designations'  => Designation::orderBy('designation_name')->get(),
            'salaryGroups'  => SalaryGroup::orderBy('salary_group_name')->get(),
            'banks'         => Bank::orderBy('bank_name')->get(),
        ]);
    }

    public function update(Request $req, $empId)
    {
        $emp = Employee::where('emp_id', $empId)->firstOrFail();

        $editable = [
            'first_name','middle_name','last_name','full_name','fathers_name','mothers_name',
            'dob','gender','marital_status','blood_group','nationality',
            'personal_mobile','personal_email','company_email','company_mobile',
            'dept_id','designation_id','salary_group_id','employment_status','employee_type',
            'date_of_joining','cost_center','business_unit',
            'current_basic','current_hra','current_da','current_conv','current_med',
            'current_spl','current_gross','current_ctc','education_allow',
            'bank_id','bank_account_no','bank_ifsc',
            'pan_no','aadhar_id_no','uan','epf_member_id','esi_ip_no','tax_regime',
        ];

        $data = [];
        foreach ($editable as $key) {
            if ($req->has($key)) {
                $val = $req->input($key);
                $data[$key] = $val === '' ? null : $val;
            }
        }
        // Auto-derive full_name if blank
        if (empty($data['full_name'])) {
            $data['full_name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        }

        try {
            $emp->update($data);
        } catch (\Throwable $ex) {
            \Log::error('Employee update failed: ' . $ex->getMessage(), ['emp_id' => $empId, 'data' => $data]);
            return back()->withInput()->withErrors([
                'update' => 'Failed to update employee: ' . $ex->getMessage(),
            ]);
        }

        return redirect()->route('employees.show', $emp->emp_id)
            ->with('status', 'Employee updated successfully.');
    }
}
