<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            $query = Employee::with(['department', 'designation', 'salary_group', 'company']);

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
            if ($req->filled('emp_type')) $query->whereIn('employee_type',   $this->expandEmpType($req->emp_type));
            if ($req->filled('status'))   $query->where('employment_status', $req->status);

            $format = $req->input('export');
            if (in_array($format, ['csv', 'xls'], true)) {
                return $this->exportEmployees($format, (clone $query)->orderBy('emp_id')->get());
            }

            return view('employees.index', [
                'employees'    => $query->orderBy('emp_id')->paginate(50),
                'departments'  => Department::when($cid, fn($q) => $q->where('company_id', $cid))->get(),
                'designations' => Designation::all(),
            ]);
        } catch (\Throwable $e) {
            return $this->stub('index — ' . $e->getMessage());
        }
    }

    /**
     * Map an emp_type filter value (ST/SB/WK) to every alias stored in the DB.
     * employee_type is free-text string(50); different seeders/imports saved it
     * as either the short code ('WK','SB','ST') or the full label ('Worker',
     * 'Sub-Staff','Staff'), so a strict equality filter missed half the rows.
     */
    private function expandEmpType(?string $value): array
    {
        $key = strtolower(trim((string) $value));
        return match ($key) {
            'wk', 'worker', 'w', 'l', 'labour', 'labor'      => ['WK', 'wk', 'Worker', 'worker', 'W', 'L', 'Labour', 'labour'],
            'sb', 'sub-staff', 'substaff', 'sub_staff', 'ss' => ['SB', 'sb', 'Sub-Staff', 'sub-staff', 'SubStaff', 'Sub Staff'],
            'st', 'staff'                                    => ['ST', 'st', 'Staff', 'staff'],
            default                                          => [$value],
        };
    }

    /**
     * CSV / Excel export of Manage Employee listing.
     * Uses HTML-as-XLS trick (same as LeaveBalanceController / StatutoryController) — no extra package.
     */
    protected function exportEmployees(string $format, $employees)
    {
        $headers = [
            'Emp ID','T.P. Code','Name','Father/Husband','Relation','Gender','DOB','Marital Status',
            'Type','Status','Company','Department','Designation','Salary Group',
            'DOJ','Mobile','Email','PAN','Aadhaar','UAN','EPF Member ID','ESI IP No',
            'Bank A/C','IFSC','Address','Current Gross',
        ];

        $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d-M-Y') : '';

        $data = $employees->map(function ($e) use ($fmtDate) {
            $isFemaleMarried = $e->marital_status === 'Married' && in_array(strtolower($e->gender ?? ''), ['female','f']);
            $relName   = $e->relative_name ?: ($isFemaleMarried ? ($e->spouse_name ?: $e->fathers_name) : ($e->fathers_name ?: $e->spouse_name));
            $relPrefix = $e->relation_prefix ?: ($isFemaleMarried && $e->spouse_name ? 'W/O' : ($e->fathers_name ? 'S/O' : ''));

            return [
                $e->emp_id,
                $e->third_party_code,
                $e->full_name,
                $relName,
                $relPrefix,
                $e->gender,
                $fmtDate($e->dob),
                $e->marital_status,
                $e->employee_type,
                $e->employment_status,
                $e->company->company_name ?? '',
                $e->department->dept_name ?? '',
                $e->designation->designation_name ?? '',
                $e->salary_group->salary_group_name ?? '',
                $fmtDate($e->date_of_joining),
                $e->mobile_no,
                $e->email,
                $e->pan_no,
                $e->aadhaar_no,
                $e->uan,
                $e->epf_member_id,
                $e->esi_ip_no,
                $e->bank_account_no,
                $e->bank_ifsc,
                $e->current_address ?? $e->permanent_address ?? '',
                (float)($e->current_gross ?? 0),
            ];
        })->all();

        $stem = 'manage-employee-' . date('Y-m-d');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $data) {
                $h = fopen('php://output', 'w');
                fwrite($h, "\xEF\xBB\xBF");
                fputcsv($h, $headers);
                foreach ($data as $r) fputcsv($h, $r);
                fclose($h);
            }, "{$stem}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $html  = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel'>";
        $html .= "<head><meta charset='UTF-8'><title>Manage Employee</title>";
        $html .= "<style>td,th{border:1px solid #888;padding:4px 6px;font-size:11px} th{background:#E5E7EB;font-weight:bold} td.num{text-align:right}</style></head><body>";
        $html .= "<h3>Manage Employee — Master List</h3><table><thead><tr>";
        foreach ($headers as $h) $html .= "<th>" . e($h) . "</th>";
        $html .= "</tr></thead><tbody>";
        foreach ($data as $r) {
            $html .= "<tr>";
            foreach ($r as $cell) {
                if (is_numeric($cell)) {
                    $val     = (float) $cell;
                    $hasFrac = abs($val - round($val)) > 0.0001;
                    $mask    = $hasFrac ? '#\\,##0.00' : '#\\,##0';
                    $html .= "<td class='num' style=\"mso-number-format:'{$mask}'\">" . e((string)$cell) . "</td>";
                } else {
                    $html .= "<td style='mso-number-format:\"\\@\"'>" . e((string)$cell) . "</td>";
                }
            }
            $html .= "</tr>";
        }
        $html .= "</tbody></table></body></html>";

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $stem . '.xls"',
        ]);
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
            'photo'           => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
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
            if ($req->hasFile('photo')) {
                $emp->photo_path = $this->storeEmployeePhoto($req->file('photo'), $emp->emp_id);
                $emp->save();
            }
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

        $req->validate([
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $editable = [
            'first_name','middle_name','last_name','full_name','fathers_name','mothers_name',
            'relation_prefix','relative_name','spouse_name',
            'dob','gender','marital_status','blood_group','nationality',
            'personal_mobile','personal_email','company_email','company_mobile',
            'dept_id','designation_id','salary_group_id','employment_status','employee_type',
            'date_of_joining','date_of_relieving','cost_center','business_unit',
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

            if ($req->hasFile('photo')) {
                $this->deleteEmployeePhoto($emp->photo_path);
                $emp->photo_path = $this->storeEmployeePhoto($req->file('photo'), $emp->emp_id);
                $emp->save();
            } elseif ($req->boolean('remove_photo')) {
                $this->deleteEmployeePhoto($emp->photo_path);
                $emp->photo_path = null;
                $emp->save();
            }
        } catch (\Throwable $ex) {
            \Log::error('Employee update failed: ' . $ex->getMessage(), ['emp_id' => $empId, 'data' => $data]);
            return back()->withInput()->withErrors([
                'update' => 'Failed to update employee: ' . $ex->getMessage(),
            ]);
        }

        return redirect()->route('employees.show', $emp->emp_id)
            ->with('status', 'Employee updated successfully.');
    }

    /** Store an uploaded photo on the `public` disk and return its relative path. */
    private function storeEmployeePhoto(\Illuminate\Http\UploadedFile $file, int $empId): string
    {
        $ext  = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $name = 'emp_'.$empId.'_'.time().'.'.$ext;
        return $file->storeAs('employees/photos', $name, 'public');
    }

    /** Remove the previous photo from the `public` disk if any. */
    private function deleteEmployeePhoto(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
