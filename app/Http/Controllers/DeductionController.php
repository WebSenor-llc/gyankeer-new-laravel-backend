<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LoanAdvance;
use App\Models\ManualDeduction;
use App\Models\Payslip;
use App\Models\SalaryRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Salary Deductions Controller — SUGAM HR-style multi-field deduction form.
 *
 * Routes:
 *   GET  /payroll/salary-deductions               → listing()
 *   GET  /payroll/salary-deductions/create        → create()        (Add Salary Deduction form)
 *   POST /payroll/salary-deductions               → store()
 *   GET  /payroll/salary-deductions/{empId}/edit  → edit()          (pre-fills the form for one employee)
 *   POST /payroll/salary-deductions/tds-quick     → updateTdsQuick() (single-field TDS edit)
 *   GET  /payroll/post-deductions                 → index()         (Post-payroll deductions / loans)
 */
class DeductionController extends Controller
{
    private function period(Request $req): array
    {
        return [
            (int) $req->input('year',  now()->year),
            (int) $req->input('month', now()->month),
        ];
    }

    public function listing(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid = (int) session('active_company_id', 0);

        $runIds = SalaryRun::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->pluck('run_id');

        $payslips = Payslip::whereIn('run_id', $runIds)->get()->keyBy('emp_id');

        // Pull pre-entered manual deductions for the period so we can show them
        // in the listing even when payroll hasn't been run yet.
        $manualByEmp = collect();
        if (Schema::hasTable('manual_deductions')) {
            $manualByEmp = ManualDeduction::where('period_year', $year)
                ->where('period_month', $month)
                ->get()
                ->keyBy('emp_id');
        }

        $employeeQ = Employee::where('active_flag', true);
        if ($cid) $employeeQ->where('company_id', $cid);

        // EMP ID FILTER — for "filter by emp id then edit TDS" workflow
        if ($req->filled('emp_id')) {
            $employeeQ->where('emp_id', $req->emp_id);
        }
        if ($req->filled('q')) {
            $q = $req->q;
            $employeeQ->where(function ($w) use ($q) {
                $w->where('full_name', 'like', "%$q%")->orWhere('emp_id', 'like', "%$q%");
            });
        }

        $employees = $employeeQ->orderBy('emp_id')->paginate(50)->appends($req->query());

        $rows = $employees->getCollection()->map(function ($e) use ($payslips, $manualByEmp, $year, $month) {
            $p = $payslips->get($e->emp_id);
            $m = $manualByEmp->get($e->emp_id);

            // Prefer payslip values when present; otherwise show pre-entered manual values
            $tds      = $p ? (float) $p->tds              : ($m ? (float) $m->tds_deduction     : 0);
            $loan     = $p ? (float) $p->loan_emi         : ($m ? (float) $m->loan_deduction    : 0);
            $advance  = $p ? (float) $p->advance_recovery : ($m ? (float) $m->advance_deduction : 0);
            $postDed  = $p ? (float) $p->post_deduction   : ($m ? $m->postDeductionTotal()      : 0);

            $vpf = $p && isset($p->vpf) ? (float) $p->vpf : ($m ? (float) ($m->vpf_deduction ?? 0) : 0);
            return [
                'emp_id'        => $e->emp_id,
                'name'          => $e->full_name,
                'epf'           => (float) ($p->epf_emp ?? 0),
                'esi'           => (float) ($p->esi_emp ?? 0),
                'pt'            => (float) ($p->pt ?? 0),
                'lwf'           => (float) ($p->lwf_emp ?? 0),
                'tds'           => $tds,
                'vpf'           => $vpf,
                'loan_emi'      => $loan,
                'advance'       => $advance,
                'fine'          => (float) ($p->fine_recovery ?? 0),
                'post_ded'      => $postDed,
                'total'         => (float) ($p->total_deductions ?? 0),
                'has_payslip'   => $p !== null,
                'has_manual'    => $m !== null,
                'period_year'   => $year,
                'period_month'  => $month,
            ];
        });

        $totals = [
            'epf'     => $rows->sum('epf'),
            'esi'     => $rows->sum('esi'),
            'pt'      => $rows->sum('pt'),
            'lwf'     => $rows->sum('lwf'),
            'tds'     => $rows->sum('tds'),
            'vpf'     => $rows->sum('vpf'),
            'loan'    => $rows->sum('loan_emi'),
            'advance' => $rows->sum('advance'),
            'fine'    => $rows->sum('fine'),
            'post'    => $rows->sum('post_ded'),
            'total'   => $rows->sum('total'),
        ];

        return view('payroll.deductions.listing', compact('rows', 'employees', 'year', 'month', 'totals'));
    }

    public function index(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid = (int) session('active_company_id', 0);
        $loans = LoanAdvance::with('emp')
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('repayment_status', 'Active')
            ->orderBy('emp_id')
            ->paginate(50);
        return view('payroll.deductions.post', compact('loans', 'year', 'month'));
    }

    /**
     * Render the SUGAM-style "Add Salary Deduction" form.
     * Optionally pre-populates from existing manual_deductions for (emp × period).
     */
    public function create(Request $req)
    {
        $cid = (int) session('active_company_id', 0);
        $employees = Employee::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('active_flag', true)
            ->orderBy('emp_id')
            ->get(['emp_id', 'full_name']);

        [$year, $month] = $this->period($req);
        $empId = $req->input('emp_id');

        // If an emp_id is in the URL, pre-fill the existing manual deductions row
        $existing = null;
        if ($empId && Schema::hasTable('manual_deductions')) {
            $existing = ManualDeduction::where('emp_id', $empId)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->first();
        }

        // Also fetch the auto-computed payslip values so user can see what payroll engine produced
        $payslip = null;
        if ($empId) {
            $emp = Employee::where('emp_id', $empId)->first();
            if ($emp) {
                $runId = SalaryRun::where('company_id', $emp->company_id)
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->value('run_id');
                if ($runId) {
                    $payslip = Payslip::where('run_id', $runId)->where('emp_id', $empId)->first();
                }
            }
        }

        return view('payroll.deductions.create', compact('employees', 'existing', 'payslip', 'year', 'month', 'empId'));
    }

    /**
     * Edit shortcut — loads create form pre-populated for the given employee.
     */
    public function edit(int $empId, Request $req)
    {
        $req->merge(['emp_id' => $empId]);
        return $this->create($req);
    }

    /**
     * Save SUGAM-style multi-line deduction. Updates the (emp × period) row in
     * manual_deductions, then re-applies all the line items to the payslip and
     * recomputes total_deductions + net_pay.
     */
    public function store(Request $req)
    {
        $req->validate([
            'emp_id'       => 'required|integer',
            'period_year'  => 'required|integer',
            'period_month' => 'required|integer|between:1,12',
            'advance_deduction'  => 'nullable|numeric|min:0',
            'loan_deduction'     => 'nullable|numeric|min:0',
            'ag_donation'        => 'nullable|numeric|min:0',
            'maintenance_charge' => 'nullable|numeric|min:0',
            'mobile_deduction'   => 'nullable|numeric|min:0',
            'canteen_deduction'  => 'nullable|numeric|min:0',
            'tds_deduction'      => 'nullable|numeric|min:0',
            'vpf_deduction'      => 'nullable|numeric|min:0',
            'incentive_hours'    => 'nullable|numeric|min:0',
            'misc_deduction'     => 'nullable|numeric|min:0',
            'rent_meridian'      => 'nullable|numeric|min:0',
            'remarks'            => 'nullable|string|max:1000',
            'tds_override_flag'  => 'nullable|boolean',
        ]);

        $emp = Employee::where('emp_id', $req->emp_id)->first();
        if (!$emp) return back()->with('status', 'Employee not found.');

        // Manual deductions are entered BEFORE payroll runs. We do NOT require an
        // existing salary run / payslip — the payroll engine will read these
        // entries when it computes the payslip later.
        $runId = SalaryRun::where('company_id', $emp->company_id)
            ->where('period_year', $req->period_year)
            ->where('period_month', $req->period_month)
            ->value('run_id');
        $ps = $runId ? Payslip::where('run_id', $runId)->where('emp_id', $req->emp_id)->first() : null;

        // Read all line items as floats up-front
        $advance     = (float) ($req->advance_deduction  ?? 0);
        $loan        = (float) ($req->loan_deduction     ?? 0);
        $agDonation  = (float) ($req->ag_donation        ?? 0);
        $maintenance = (float) ($req->maintenance_charge ?? 0);
        $mobile      = (float) ($req->mobile_deduction   ?? 0);
        $canteen     = (float) ($req->canteen_deduction  ?? 0);
        $tdsValue    = (float) ($req->tds_deduction      ?? 0);
        $vpfValue    = (float) ($req->vpf_deduction      ?? 0);
        $incentive   = (float) ($req->incentive_hours    ?? 0);
        $misc        = (float) ($req->misc_deduction     ?? 0);
        $rent        = (float) ($req->rent_meridian      ?? 0);
        $tdsOverride = $req->boolean('tds_override_flag') || $tdsValue > 0;

        // 1) Persist the SUGAM line items to manual_deductions if the table exists.
        //    If the migration hasn't been run yet, we skip this step but still apply
        //    the deduction to the payslip below — so the user's edit is never lost.
        $manualSavedNote = '';
        if (Schema::hasTable('manual_deductions')) {
            ManualDeduction::updateOrCreate(
                [
                    'emp_id'       => $emp->emp_id,
                    'period_year'  => (int) $req->period_year,
                    'period_month' => (int) $req->period_month,
                ],
                [
                    'company_id'         => $emp->company_id,
                    'payslip_id'         => $ps?->payslip_id,
                    'advance_deduction'  => $advance,
                    'loan_deduction'     => $loan,
                    'ag_donation'        => $agDonation,
                    'maintenance_charge' => $maintenance,
                    'mobile_deduction'   => $mobile,
                    'canteen_deduction'  => $canteen,
                    'tds_deduction'      => $tdsValue,
                    'vpf_deduction'      => $vpfValue,
                    'incentive_hours'    => $incentive,
                    'misc_deduction'     => $misc,
                    'rent_meridian'      => $rent,
                    'remarks'            => $req->remarks,
                    'tds_override_flag'  => $tdsOverride,
                    'created_by_user_id' => auth()->id(),
                ]
            );
        } else {
            return back()->with('status', 'manual_deductions table missing — run: php artisan migrate');
        }

        // 2) ── If a payslip already exists for this period, update it now too,
        //       so HR sees the values reflected immediately. If no payslip yet,
        //       payroll engine will pick up the manual_deductions row when it runs.
        if ($ps) {
            $ps->advance_recovery = $advance;
            $ps->loan_emi         = $loan;
            if ($tdsOverride) {
                $ps->tds = $tdsValue;
            }
            // VPF — only set if the column exists (post-migration)
            if (\Illuminate\Support\Facades\Schema::hasColumn('payslips', 'vpf')) {
                $ps->vpf = $vpfValue;
            }
            $ps->post_deduction = $agDonation + $maintenance + $mobile + $canteen + $misc + $rent;
            $vpfFromPs = \Illuminate\Support\Facades\Schema::hasColumn('payslips','vpf') ? (float) ($ps->vpf ?? 0) : 0.0;
            $ps->total_deductions = (float) $ps->epf_emp + (float) $ps->esi_emp + (float) $ps->pt
                + (float) $ps->lwf_emp + (float) $ps->tds + $vpfFromPs
                + (float) $ps->loan_emi + (float) $ps->advance_recovery + (float) $ps->fine_recovery
                + (float) $ps->post_deduction;
            $ps->net_pay = (float) $ps->gross_earnings - (float) $ps->total_deductions;
            $ps->save();
        }

        $msg = "Saved manual deductions for {$emp->full_name} for "
            . \DateTime::createFromFormat('!m', (int) $req->period_month)->format('F') . " {$req->period_year}.";
        if ($ps) {
            $msg .= " Existing payslip updated → TDS ₹" . number_format($ps->tds, 2)
                . " · Net Pay ₹" . number_format($ps->net_pay, 2) . ".";
        } else {
            $msg .= " No payslip exists yet — these values will be applied automatically when payroll engine runs for this period.";
        }
        return redirect()->route('deductions.listing', [
                'year'  => $req->period_year,
                'month' => $req->period_month,
                'emp_id'=> $emp->emp_id,
            ])->with('status', $msg);
    }

    /**
     * Quick TDS-only edit — single field POST from the listing row.
     * Use case: "filter by emp id, edit TDS, save".
     */
    public function updateTdsQuick(Request $req)
    {
        $req->validate([
            'emp_id'       => 'required|integer',
            'period_year'  => 'required|integer',
            'period_month' => 'required|integer|between:1,12',
            'tds'          => 'required|numeric|min:0',
        ]);

        $emp = Employee::where('emp_id', $req->emp_id)->first();
        if (!$emp) return back()->with('status', 'Employee not found.');

        $newTds = (float) $req->tds;

        // Always persist the override to manual_deductions so the engine picks it
        // up if payroll hasn't been run yet.
        if (!Schema::hasTable('manual_deductions')) {
            return back()->with('status', 'manual_deductions table missing — run: php artisan migrate');
        }

        $runId = SalaryRun::where('company_id', $emp->company_id)
            ->where('period_year', $req->period_year)
            ->where('period_month', $req->period_month)
            ->value('run_id');
        $ps = $runId ? Payslip::where('run_id', $runId)->where('emp_id', $req->emp_id)->first() : null;

        ManualDeduction::updateOrCreate(
            [
                'emp_id'       => $emp->emp_id,
                'period_year'  => (int) $req->period_year,
                'period_month' => (int) $req->period_month,
            ],
            [
                'company_id'        => $emp->company_id,
                'payslip_id'        => $ps?->payslip_id,
                'tds_deduction'     => $newTds,
                'tds_override_flag' => true,
                'created_by_user_id'=> auth()->id(),
            ]
        );

        // If payslip already exists, update it now too
        if ($ps) {
            $ps->tds = $newTds;
            $ps->total_deductions = (float) $ps->epf_emp + (float) $ps->esi_emp + (float) $ps->pt
                + (float) $ps->lwf_emp + $newTds
                + (float) $ps->loan_emi + (float) $ps->advance_recovery + (float) $ps->fine_recovery
                + (float) $ps->post_deduction;
            $ps->net_pay = (float) $ps->gross_earnings - (float) $ps->total_deductions;
            $ps->save();
            $msg = "TDS for {$emp->full_name} set to ₹" . number_format($newTds, 2) . ". Net pay updated to ₹" . number_format($ps->net_pay, 2) . ".";
        } else {
            $msg = "TDS for {$emp->full_name} pre-entered (₹" . number_format($newTds, 2) . "). Will apply when payroll runs.";
        }

        return redirect()->route('deductions.listing', [
                'year'  => $req->period_year,
                'month' => $req->period_month,
                'emp_id'=> $emp->emp_id,
            ])->with('status', $msg);
    }
}
