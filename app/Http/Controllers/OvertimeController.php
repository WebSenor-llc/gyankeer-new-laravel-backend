<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Models\SalaryGroup;
use Illuminate\Http\Request;

/**
 * Overtime Sheet & Add Overtime — SUGAM HR-style.
 *
 *   GET  /payroll/overtime-sheet            → sheet()      (filter + listing)
 *   GET  /payroll/overtime-sheet/create     → create()     (Add Overtime form)
 *   POST /payroll/overtime-sheet            → store()
 *   POST /payroll/overtime-sheet/{otId}/delete → destroy()
 */
class OvertimeController extends Controller
{
    public function sheet(Request $req)
    {
        $year  = (int) $req->input('year',  now()->year);
        $month = (int) $req->input('month', now()->month);
        $cid   = (int) session('active_company_id', 0);

        $companies   = Company::orderBy('company_name')->get();
        $salaryGroups = SalaryGroup::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('salary_group_name')->get();
        $departments = Department::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('dept_name')->get();
        $designations= Designation::orderBy('designation_name')->get();

        // Load OT entries for the period, filtered
        $q = OvertimeRecord::with(['emp.department','emp.salary_group'])
            ->where('period_year', $year)
            ->where('period_month', $month);

        if ($cid)                          $q->where('company_id', $cid);
        if ($req->filled('salary_group_id')) $q->whereHas('emp', fn($w) => $w->where('salary_group_id', $req->salary_group_id));
        if ($req->filled('dept_id'))       $q->whereHas('emp', fn($w) => $w->where('dept_id', $req->dept_id));
        if ($req->filled('designation_id')) $q->whereHas('emp', fn($w) => $w->where('designation_id', $req->designation_id));
        if ($req->filled('emp_type'))      $q->whereHas('emp', fn($w) => $w->where('employee_type', $req->emp_type));
        if ($req->filled('emp_id'))        $q->where('emp_id', $req->emp_id);

        // PDF export of the same filtered data, matching SUGAM HR's
        // "Incentive for the Month" register format.
        if ($req->input('export') === 'pdf') {
            $allRecords = (clone $q)->orderBy('emp_id')->get();
            $company    = \App\Models\Company::find($cid);
            $group      = $req->filled('salary_group_id')
                ? \App\Models\SalaryGroup::find($req->salary_group_id)
                : null;
            return view('payroll.overtime.sheet-pdf', [
                'records' => $allRecords,
                'company' => $company,
                'group'   => $group,
                'year'    => $year,
                'month'   => $month,
                'totals'  => [
                    // Amount & Net Payable print as whole rupees, so sum the ROUNDED
                    // per-row values to keep the footer equal to the printed rows.
                    'hours'   => (float) $allRecords->sum('ot_hours'),
                    'amount'  => (float) $allRecords->sum(fn($r) => round((float)$r->ot_amount)),
                    'esi'     => (float) $allRecords->sum(fn($r) => (float)($r->ot_esi ?? 0)),
                    'payable' => (float) $allRecords->sum(fn($r) => round((float)($r->ot_payable ?? $r->ot_amount))),
                ],
            ]);
        }

        $records = $q->orderBy('emp_id')->paginate(50)->appends($req->query());

        $totalsBase = OvertimeRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->when($cid, fn($x) => $x->where('company_id', $cid));
        $totals = [
            'count'   => (clone $totalsBase)->count(),
            'hours'   => (float) (clone $totalsBase)->sum('ot_hours'),
            'amount'  => (float) (clone $totalsBase)->sum('ot_amount'),
            'esi'     => \Illuminate\Support\Facades\Schema::hasColumn('overtime_records','ot_esi')
                            ? (float) (clone $totalsBase)->sum('ot_esi') : 0.0,
            'payable' => \Illuminate\Support\Facades\Schema::hasColumn('overtime_records','ot_payable')
                            ? (float) (clone $totalsBase)->sum('ot_payable') : 0.0,
        ];

        return view('payroll.overtime.sheet', compact(
            'records', 'totals', 'year', 'month',
            'companies', 'salaryGroups', 'departments', 'designations'
        ));
    }

    public function create(Request $req)
    {
        $cid       = (int) session('active_company_id', 0);
        $year      = (int) $req->input('year',  now()->year);
        $month     = (int) $req->input('month', now()->month);
        $empId     = (int) $req->input('emp_id', 0);

        $employees = Employee::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('active_flag', true)
            ->orderBy('emp_id')->get(['emp_id','full_name']);

        // If editing an existing OT row
        $existing = null;
        if ($empId) {
            $existing = OvertimeRecord::where('emp_id', $empId)
                ->where('period_year', $year)->where('period_month', $month)->first();
        }

        // For the live preview helper in the form: send hourly wages keyed by emp_id
        $hourlyByEmp = [];
        foreach ($employees as $e) {
            $hourlyByEmp[$e->emp_id] = OvertimeRecord::hourlyWage($e);
        }
        // Also send ESI eligibility flag (gross ≤ ₹21,000)
        $esiEligibleByEmp = [];
        $allEmps = Employee::when($cid, fn($q) => $q->where('company_id', $cid))->where('active_flag', true)->get(['emp_id','current_gross']);
        foreach ($allEmps as $e) {
            $esiEligibleByEmp[$e->emp_id] = ((float)$e->current_gross > 0 && (float)$e->current_gross <= 21000);
        }

        return view('payroll.overtime.create', compact(
            'employees', 'year', 'month', 'empId', 'existing',
            'hourlyByEmp', 'esiEligibleByEmp'
        ));
    }

    public function store(Request $req)
    {
        $req->validate([
            'emp_id'       => 'required|integer',
            'period_year'  => 'required|integer|between:2020,2030',
            'period_month' => 'required|integer|between:1,12',
            'ot_rate'      => 'required|numeric|min:0|max:5',
            'ot_hours'     => 'required|numeric|min:0|max:744',
            'notes'        => 'nullable|string|max:500',
        ]);

        $emp = Employee::where('emp_id', $req->emp_id)->first();
        if (!$emp) return back()->with('status', 'Employee not found.');

        $rate   = (float) $req->ot_rate;
        $hours  = (float) $req->ot_hours;
        $b      = OvertimeRecord::computeBreakdown($emp, $rate, $hours);

        OvertimeRecord::updateOrCreate(
            ['emp_id' => $emp->emp_id, 'period_year' => $req->integer('period_year'), 'period_month' => $req->integer('period_month')],
            [
                'company_id'         => $emp->company_id,
                'ot_rate'            => $rate,
                'hourly_rate'        => $b['hourly_rate'],
                'ot_hours'           => $hours,
                'ot_amount'          => $b['ot_amount'],
                'ot_esi'             => $b['ot_esi'],
                'ot_payable'         => $b['ot_payable'],
                'notes'              => $req->notes,
                'created_by_user_id' => auth()->id(),
            ]
        );

        return redirect()->route('overtime-sheet', ['year' => $req->period_year, 'month' => $req->period_month])
            ->with('status', "Saved OT for {$emp->full_name}: {$hours} hrs × {$rate}× @ ₹"
                . number_format($b['hourly_rate'], 2) . "/hr = ₹" . number_format($b['ot_amount'], 2)
                . " (ESI ₹" . number_format($b['ot_esi'], 2) . " · Payable ₹" . number_format($b['ot_payable'], 2)
                . "). Adds to gross when payroll runs.");
    }

    public function destroy($otId)
    {
        $rec = OvertimeRecord::find($otId);
        if (!$rec) return back()->with('status', 'OT record not found.');
        $rec->delete();
        return back()->with('status', 'OT entry removed.');
    }
}
