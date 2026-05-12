<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PfEcrRecord;
use App\Models\EsiRecord;
use App\Models\PtRecord;
use App\Models\LwfRecord;
use App\Models\TdsRecord;
use App\Models\Form24qRecord;
use App\Models\BonusProvision;
use App\Models\GratuityRegister;
use Illuminate\Http\Request;

/**
 * Statutory & Compliance Controller
 *
 * Renders India-specific statutory views (PF / ESI / PT / LWF / TDS / Form 24Q
 * / Form 16 / Bonus / Gratuity / POSH / Calendar). Each view supports period
 * selection (year/month) and renders rows from the matching record tables.
 */
class StatutoryController extends Controller
{
    private function period(Request $req): array
    {
        return [
            (int) $req->input('year',  now()->year),
            (int) $req->input('month', now()->month),
        ];
    }

    public function pfChallan(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        $rows = PfEcrRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($salaryGroupId, fn($q) => $q->whereIn(
                'emp_id',
                \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ))
            ->get();

        $totals = [
            'ee'    => $rows->sum('ee_share_12pct'),
            'eps'   => $rows->sum('eps_8_33'),
            'er'    => $rows->sum('er_share_3_67'),
            'edli'  => $rows->sum('edli_0_5'),
            'admin' => $rows->sum('pf_admin_0_5'),
        ];
        $totals['challan'] = array_sum($totals);

        $salaryGroups = $this->salaryGroups($cid);

        return view('statutory.pf-challan', compact(
            'rows', 'totals', 'year', 'month', 'salaryGroups', 'salaryGroupId'
        ));
    }

    /**
     * Salary groups list, scoped to the active company when present.
     */
    protected function salaryGroups(int $cid)
    {
        return \App\Models\SalaryGroup::query()
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->orderBy('salary_group_name')
            ->get();
    }

    /**
     * Resolve the current company row for headers on PDF/views. Falls back
     * to the first company when no active_company_id is set in session.
     */
    protected function companyForHeader(int $cid)
    {
        if ($cid) return \App\Models\Company::find($cid);
        return \App\Models\Company::orderBy('company_id')->first();
    }

    /**
     * Generate (or regenerate) PF ECR records from existing payslips for the
     * given period. Reads any payslip with non-zero EPF — does NOT require the
     * salary run to be 'Posted'. Wipes prior ECR rows for the same period
     * before re-inserting so re-runs are idempotent.
     */
    public function generateEcr(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        // Pull all payslips for the period (filter by active company if set)
        $runIds = \App\Models\SalaryRun::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('period_year',  $year)
            ->where('period_month', $month)
            ->pluck('run_id');

        if ($runIds->isEmpty()) {
            return back()->with('status', "No salary run exists for {$month}/{$year}. Generate salary first via /payroll/generate.");
        }

        // Optional salary-group scope
        $groupEmpIds = $salaryGroupId
            ? \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            : null;

        $payslips = \App\Models\Payslip::with('emp')
            ->whereIn('run_id', $runIds)
            ->where('epf_emp', '>', 0)   // only employees with PF deduction
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->get();

        if ($payslips->isEmpty()) {
            $scope = $salaryGroupId ? " in selected group" : "";
            return back()->with('status', "No payslips with PF deduction found for {$month}/{$year}{$scope}. Run payroll first.");
        }

        // Wipe prior ECR rows for this period — narrowed to the group when one
        // is picked, so generating one group doesn't delete others' ECR rows.
        \App\Models\PfEcrRecord::where('period_year',  $year)
            ->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->forceDelete();

        $inserted = 0;
        foreach ($payslips as $p) {
            $emp = $p->emp;
            if (!$emp) continue;

            // NCP days = LOP days (Non-Contributory Period for EPFO reporting)
            $ncpDays = (int) round((float) $p->lop_days);

            \App\Models\PfEcrRecord::create([
                'period_year'      => $year,
                'period_month'     => $month,
                'company_id'       => $emp->company_id,
                'emp_id'           => $emp->emp_id,
                'uan'              => $emp->uan ?? '',
                'member_name'      => $emp->full_name ?? '',
                // gross + the three capped wage columns: PF / EPS / EDLI all
                // computed on the same wage base (Basic + DA + Conv + Med + Spl,
                // capped at ₹15,000) per the company's PF rule.
                'gross_wage'       => (float) $p->gross_earnings,
                'epf_wage_capped'  => $this->pfWageFromPayslip($p),
                'eps_wage_capped'  => $this->pfWageFromPayslip($p),
                'edli_wage_capped' => $this->pfWageFromPayslip($p),
                'ee_share_12pct'   => (float) $p->epf_emp,
                'eps_8_33'         => (float) $p->eps,
                'er_share_3_67'    => (float) $p->employer_pf,
                'edli_0_5'         => (float) $p->edli,
                'pf_admin_0_5'     => (float) $p->pf_admin,
                'ncp_days'         => $ncpDays,
                'lop_amount'       => 0,
                'filed_status'     => 'Generated',
            ]);
            $inserted++;
        }

        return redirect()->route('statutory.pf', [
                'year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId,
            ])
            ->with('status', "✅ Generated {$inserted} PF ECR row(s) for "
                . \DateTime::createFromFormat('!m', $month)->format('F') . " {$year}"
                . ($salaryGroupId ? ' (group filtered)' : '') . '. Ready for EPFO upload.');
    }

    /**
     * Reverse-engineer the PF wage from a payslip: ee_share_12pct ÷ 12%.
     * Falls back to capped (Basic+DA+Conv+Med+Spl) calculation if needed.
     */
    protected function pfWageFromPayslip($p): float
    {
        if ((float) $p->epf_emp > 0) {
            return round((float) $p->epf_emp / 0.12, 2);
        }
        // Fallback: company's PF wage rule — sum minus HRA, capped at 15k
        $wage = (float) $p->basic + (float) $p->da
              + (float) ($p->conveyance ?? 0) + (float) ($p->medical ?? 0)
              + (float) ($p->spl_allow ?? 0);
        return round(min($wage, 15000), 2);
    }

    public function esiChallan(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        $rows = EsiRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($salaryGroupId, fn($q) => $q->whereIn(
                'emp_id',
                \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ))
            ->get();
        $totals = [
            'ee'    => $rows->sum('ee_0_75'),
            'er'    => $rows->sum('er_3_25'),
            'total' => $rows->sum('total_contribution'),
        ];
        $salaryGroups = $this->salaryGroups($cid);
        return view('statutory.esi-challan', compact(
            'rows', 'totals', 'year', 'month', 'salaryGroups', 'salaryGroupId'
        ));
    }

    /**
     * Generate ESI records from existing payslips for the period.
     * No "posted run" check — works on any payslips with esi_emp > 0.
     */
    public function generateEsi(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        $runIds = \App\Models\SalaryRun::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('period_year', $year)->where('period_month', $month)->pluck('run_id');
        if ($runIds->isEmpty()) return back()->with('status', "No salary run for {$month}/{$year}.");

        $groupEmpIds = $salaryGroupId
            ? \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            : null;

        $payslips = \App\Models\Payslip::with('emp')
            ->whereIn('run_id', $runIds)
            ->where('esi_emp', '>', 0)
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->get();

        EsiRecord::where('period_year', $year)->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->forceDelete();

        $count = 0;
        foreach ($payslips as $p) {
            if (!$p->emp) continue;
            EsiRecord::create([
                'period_year'  => $year,
                'period_month' => $month,
                'company_id'   => $p->emp->company_id,
                'emp_id'       => $p->emp_id,
                'ip_no'        => $p->emp->esi_ip_no ?? '',
                'member_name'  => $p->emp->full_name ?? '',
                'gross_wage'   => (float) $p->gross_earnings,
                'days_worked'  => round((float) $p->payable_days, 1),
                'ee_0_75'      => (float) $p->esi_emp,
                'er_3_25'      => (float) $p->employer_esi,
                'total_contribution' => (float) $p->esi_emp + (float) $p->employer_esi,
                'filed_status' => 'Generated',
            ]);
            $count++;
        }

        return redirect()->route('statutory.esi', [
                'year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId,
            ])
            ->with('status', "✅ Generated {$count} ESI row(s) for "
                . \DateTime::createFromFormat('!m', $month)->format('F') . " {$year}"
                . ($salaryGroupId ? ' (group filtered)' : '') . '.');
    }

    public function pt(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        $rows = PtRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($salaryGroupId, fn($q) => $q->whereIn(
                'emp_id',
                \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ))
            ->get();
        $total = $rows->sum('pt_amount');
        $salaryGroups = $this->salaryGroups($cid);
        return view('statutory.pt', compact(
            'rows', 'year', 'month', 'total', 'salaryGroups', 'salaryGroupId'
        ));
    }

    /**
     * Generate PT records from existing payslips. PT > 0 only.
     */
    public function generatePt(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        $runIds = \App\Models\SalaryRun::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('period_year', $year)->where('period_month', $month)->pluck('run_id');
        if ($runIds->isEmpty()) return back()->with('status', "No salary run for {$month}/{$year}.");

        $groupEmpIds = $salaryGroupId
            ? \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            : null;

        $payslips = \App\Models\Payslip::with('emp')
            ->whereIn('run_id', $runIds)
            ->where('pt', '>', 0)
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->get();

        PtRecord::where('period_year', $year)->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->forceDelete();

        $count = 0;
        foreach ($payslips as $p) {
            if (!$p->emp) continue;
            PtRecord::create([
                'period_year'    => $year,
                'period_month'   => $month,
                'company_id'     => $p->emp->company_id,
                'emp_id'         => $p->emp_id,
                'employee_name'  => $p->emp->full_name ?? '',
                'state'          => $p->emp->pt_state ?? 'RJ',
                'pt_amount'      => (float) $p->pt,
                'status'         => 'Generated',
            ]);
            $count++;
        }

        return redirect()->route('statutory.pt', [
                'year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId,
            ])
            ->with('status', "✅ Generated {$count} PT row(s) for "
                . \DateTime::createFromFormat('!m', $month)->format('F') . " {$year}"
                . ($salaryGroupId ? ' (group filtered)' : '') . '. '
                . ($count === 0 ? 'No employees in PT-applicable states have PT > 0.' : ''));
    }

    public function lwf(Request $req)
    {
        $year          = (int) $req->input('year', now()->year);
        $half          = $req->input('half', now()->month <= 6 ? 'H1' : 'H2');
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        $rows = LwfRecord::where('period_year', $year)
            ->where('period_half', $half)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($salaryGroupId, fn($q) => $q->whereIn(
                'emp_id',
                \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ))
            ->get();
        $total = $rows->sum('total_contribution');
        $salaryGroups = $this->salaryGroups($cid);
        return view('statutory.lwf', compact(
            'rows', 'year', 'half', 'total', 'salaryGroups', 'salaryGroupId'
        ));
    }

    /**
     * Generate LWF records (half-yearly H1/H2) from payslips of the half period.
     */
    public function generateLwf(Request $req)
    {
        $year          = (int) $req->input('year', now()->year);
        $half          = $req->input('half', 'H1');
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        // H1 = Jan–Jun, H2 = Jul–Dec
        $months = $half === 'H1' ? [1,2,3,4,5,6] : [7,8,9,10,11,12];

        $runIds = \App\Models\SalaryRun::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('period_year', $year)
            ->whereIn('period_month', $months)
            ->pluck('run_id');
        if ($runIds->isEmpty()) return back()->with('status', "No salary runs for {$half} {$year}.");

        $groupEmpIds = $salaryGroupId
            ? \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            : null;

        // Sum LWF per employee across the half
        $payslips = \App\Models\Payslip::with('emp')
            ->whereIn('run_id', $runIds)
            ->where(function ($q) {
                $q->where('lwf_emp', '>', 0)->orWhere('lwf_employer', '>', 0);
            })
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->get()
            ->groupBy('emp_id');

        LwfRecord::where('period_year', $year)->where('period_half', $half)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->forceDelete();

        $count = 0;
        foreach ($payslips as $empId => $slips) {
            $emp = $slips->first()->emp;
            if (!$emp) continue;
            $ee = $slips->sum('lwf_emp');
            $er = $slips->sum('lwf_employer');
            if ($ee + $er <= 0) continue;
            LwfRecord::create([
                'company_id'             => $emp->company_id,
                'emp_id'                 => $emp->emp_id,
                'employee_name'          => $emp->full_name ?? '',
                'state'                  => $emp->lwf_state ?? 'RJ',
                'period_year'            => $year,
                'period_half'            => $half,
                'employee_contribution'  => $ee,
                'employer_contribution'  => $er,
                'total_contribution'     => $ee + $er,
                'status'                 => 'Generated',
            ]);
            $count++;
        }

        return redirect()->route('statutory.lwf', [
                'year' => $year, 'half' => $half, 'salary_group_id' => $salaryGroupId,
            ])
            ->with('status', "✅ Generated {$count} LWF row(s) for {$half} {$year}"
                . ($salaryGroupId ? ' (group filtered)' : '') . '.');
    }

    public function tds(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        $groupEmpIds = $salaryGroupId
            ? \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            : null;

        // Pull from saved tds_records first; if empty, fall back to live estimate
        $rows = TdsRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->get();

        // Live estimate (from employee master) for display when no records yet
        $records = $rows->isNotEmpty()
            ? $rows->map(fn($r) => [
                'emp_id'       => $r->emp_id,
                'name'         => $r->employee_name,
                'pan'          => $r->pan,
                'regime'       => $r->regime ?? 'New',
                'annual_gross' => (float) $r->annual_gross,
                'annual_tax'   => (float) $r->total_annual_tax,
                'monthly_tds'  => (float) $r->monthly_tds_for_period,
            ])
            : Employee::where('active_flag', true)
                ->when($cid, fn($q) => $q->where('company_id', $cid))
                ->when($salaryGroupId, fn($q) => $q->where('salary_group_id', $salaryGroupId))
                ->take(500)->get()
                ->map(function ($e) {
                    $annualGross = ($e->current_gross ?? 0) * 12;
                    $annualTax = max(0, ($annualGross - 750000) * 0.10);
                    return [
                        'emp_id'       => $e->emp_id,
                        'name'         => $e->full_name,
                        'pan'          => $e->pan_no,
                        'regime'       => $e->tax_regime ?? 'New',
                        'annual_gross' => $annualGross,
                        'annual_tax'   => $annualTax,
                        'monthly_tds'  => round($annualTax / 12),
                    ];
                });

        $salaryGroups = $this->salaryGroups($cid);
        return view('statutory.tds', compact(
            'records', 'year', 'month', 'salaryGroups', 'salaryGroupId'
        ));
    }

    /**
     * Generate TDS records from payslips for the period.
     */
    public function generateTds(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);

        $runIds = \App\Models\SalaryRun::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('period_year', $year)->where('period_month', $month)->pluck('run_id');
        if ($runIds->isEmpty()) return back()->with('status', "No salary run for {$month}/{$year}.");

        $groupEmpIds = $salaryGroupId
            ? \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            : null;

        $payslips = \App\Models\Payslip::with('emp')
            ->whereIn('run_id', $runIds)
            ->where('tds', '>', 0)
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->get();

        TdsRecord::where('period_year', $year)->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($groupEmpIds !== null, fn($q) => $q->whereIn('emp_id', $groupEmpIds))
            ->forceDelete();

        $count = 0;
        foreach ($payslips as $p) {
            if (!$p->emp) continue;
            $monthlyTds = (float) $p->tds;
            $annualTax  = $monthlyTds * 12;
            TdsRecord::create([
                'period_year'             => $year,
                'period_month'            => $month,
                'company_id'              => $p->emp->company_id,
                'emp_id'                  => $p->emp_id,
                'employee_name'           => $p->emp->full_name ?? '',
                'pan'                     => $p->emp->pan_no ?? '',
                'regime'                  => $p->emp->tax_regime ?? 'New',
                'annual_gross'            => (float) ($p->gross_earnings ?? 0) * 12,
                'taxable_income'          => (float) ($p->gross_earnings ?? 0) * 12,
                'tax_before_cess'         => round($annualTax / 1.04, 2),
                'cess_4pct'               => round($annualTax * 0.04 / 1.04, 2),
                'total_annual_tax'        => $annualTax,
                'monthly_tds_proration'   => $monthlyTds,
                'monthly_tds_for_period'  => $monthlyTds,
                'ytd_tds_paid'            => $monthlyTds,
            ]);
            $count++;
        }

        return redirect()->route('statutory.tds', [
                'year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId,
            ])
            ->with('status', "✅ Generated {$count} TDS row(s) for "
                . \DateTime::createFromFormat('!m', $month)->format('F') . " {$year}"
                . ($salaryGroupId ? ' (group filtered)' : '') . '.');
    }

    // ============================================================
    // Multi-format export (PDF / CSV / XLS) for every statutory return.
    //
    //  • PDF (default) — printable A4 layout for filing/signing
    //  • CSV          — flat upload-ready data for direct portal upload
    //  • XLS          — HTML-as-Excel (no library needed); same columns as CSV
    //
    // PF/ECR uses the 30-column EPFO ECR upload schema:
    //   PFNo, UANNo, Name, Gross Wages, EPF, EPS, EDLI, EPFC, EPFR, EPSC,
    //   EPSR, DIFC, DIFR, NCP, RADV, ARREPF, ARREPFEE, ARREPER, ARREPS,
    //   FNAME, RMBR, DOB, GENDER, DOJEPF, DOJEPS, DOEEPF, DOEEPFS, REASON,
    //   Salary Group, Company
    //
    // ESI uses the 8-column ESIC upload schema:
    //   IP Number, IP Name, Payble Days, Total Monthly Wages, Reason Code,
    //   Last Working Day, S. Group, Company
    // ============================================================

    public function pfChallanPdf(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);
        $format        = strtolower($req->input('format', 'pdf'));

        $rows = PfEcrRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($salaryGroupId, fn($q) => $q->whereIn(
                'emp_id',
                \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ))
            ->orderBy('emp_id')
            ->get();

        // Round each row to whole rupees first, then sum — matches the Excel/CSV
        // exporter (which writes integers per row). Sum-then-round drifts vs
        // round-then-sum for the .50 paise produced by 8.33% / 3.67% × ₹15,000.
        $totals = [
            'gross' => $rows->sum(fn($r) => round((float) $r->gross_wage)),
            'epf'   => $rows->sum(fn($r) => round((float) $r->epf_wage_capped)),
            'eps'   => $rows->sum(fn($r) => round((float) $r->eps_wage_capped)),
            'ee'    => $rows->sum(fn($r) => round((float) $r->ee_share_12pct)),
            'eps_c' => $rows->sum(fn($r) => round((float) $r->eps_8_33)),
            'er'    => $rows->sum(fn($r) => round((float) $r->er_share_3_67)),
            'edli'  => $rows->sum(fn($r) => round((float) $r->edli_0_5)),
            'admin' => $rows->sum(fn($r) => round((float) $r->pf_admin_0_5)),
        ];
        $totals['challan'] = $totals['ee'] + $totals['eps_c'] + $totals['er']
                           + $totals['edli'] + $totals['admin'];

        $company   = $this->companyForHeader($cid);
        $group     = $salaryGroupId ? \App\Models\SalaryGroup::find($salaryGroupId) : null;
        $monthName = \DateTime::createFromFormat('!m', $month)->format('F');

        // CSV / Excel — flat EPFO ECR upload schema (30 columns)
        if (in_array($format, ['csv', 'xls'], true)) {
            $headers = [
                'PFNo', 'UANNo', 'Name', 'Gross Wages',
                'EPF', 'EPS', 'EDLI',
                'EPFC', 'EPFR', 'EPSC', 'EPSR', 'DIFC', 'DIFR',
                'NCP', 'RADV', 'ARREPF', 'ARREPFEE', 'ARREPER', 'ARREPS',
                'FNAME', 'RMBR', 'DOB', 'GENDER',
                'DOJEPF', 'DOJEPS', 'DOEEPF', 'DOEEPFS',
                'REASON', 'Salary Group', 'Company',
            ];

            $empIds = $rows->pluck('emp_id')->all();
            $emps   = \App\Models\Employee::with(['salary_group','company'])
                ->whereIn('emp_id', $empIds)->get()->keyBy('emp_id');

            $data = [];
            foreach ($rows as $r) {
                $e          = $emps->get($r->emp_id);
                $epfWage    = (float) ($r->epf_wage_capped ?? 0);
                $epsWage    = (float) ($r->eps_wage_capped ?? 0);
                $edliWage   = (float) ($r->edli_wage_capped ?? 0);
                $epfTotal   = (float) ($r->ee_share_12pct ?? 0);   // EPFC
                $epsTotal   = (float) ($r->eps_8_33 ?? 0);          // EPSC
                $erEpf      = (float) ($r->er_share_3_67 ?? 0);     // DIFC = ER share
                $erEps      = (float) ($r->eps_8_33 ?? 0);          // DIFR mirrors EPS
                $dojEpf  = $e && $e->epf_join_date     ? \Carbon\Carbon::parse($e->epf_join_date)->format('d/m/Y')
                          : ($e && $e->date_of_joining ? \Carbon\Carbon::parse($e->date_of_joining)->format('d/m/Y') : '');
                $dojEps  = $e && $e->eps_join_date     ? \Carbon\Carbon::parse($e->eps_join_date)->format('d/m/Y') : $dojEpf;
                $exit    = $e && $e->date_of_relieving ? \Carbon\Carbon::parse($e->date_of_relieving)->format('d/m/Y') : '';
                $data[] = [
                    $r->member_id_pf ?? ($e->epf_member_id ?? ''),               // PFNo
                    $r->uan ?? ($e->uan ?? ''),                                  // UANNo
                    $r->member_name ?? ($e->full_name ?? ''),                    // Name
                    (int) round((float) ($r->gross_wage ?? 0)),                  // Gross Wages
                    (int) round($epfWage),                                       // EPF wage
                    (int) round($epsWage),                                       // EPS wage
                    (int) round($edliWage),                                      // EDLI wage
                    (int) round($epfTotal),                                      // EPFC (Member 12%)
                    (int) round($epfTotal),                                      // EPFR (Recovered)
                    (int) round($epsTotal),                                      // EPSC (8.33%)
                    (int) round($epsTotal),                                      // EPSR
                    (int) round($erEpf),                                         // DIFC (ER 3.67%)
                    (int) round($erEpf),                                         // DIFR
                    (int) round((float) ($r->ncp_days ?? 0)),                    // NCP
                    0, 0, 0, 0, 0,                                               // RADV, ARREPF, ARREPFEE, ARREPER, ARREPS
                    $e->fathers_name ?? '',                                      // FNAME
                    '',                                                          // RMBR
                    $e && $e->dob ? \Carbon\Carbon::parse($e->dob)->format('d/m/Y') : '',
                    strtoupper(substr($e->gender ?? '', 0, 1)),                  // GENDER
                    $dojEpf,                                                     // DOJEPF
                    $dojEps,                                                     // DOJEPS
                    $exit,                                                       // DOEEPF
                    $exit,                                                       // DOEEPFS
                    $e->exit_reason ?? '',                                       // REASON
                    $e->salary_group->salary_group_name ?? '',
                    $e->company->company_code ?? ($company->company_code ?? ''),
                ];
            }

            $stem = 'pf-ecr-' . strtolower($monthName) . '-' . $year
                  . ($salaryGroupId && $group ? '-' . preg_replace('/[^A-Za-z0-9]+/', '-', $group->salary_group_name) : '');
            return $format === 'csv'
                ? $this->streamCsv("{$stem}.csv", $headers, $data)
                : $this->streamXls("{$stem}.xls", "PF ECR — {$monthName} {$year}", $headers, $data);
        }

        return view('statutory.pf-challan-pdf', compact(
            'rows', 'totals', 'year', 'month', 'monthName', 'company', 'group'
        ));
    }

    public function esiChallanPdf(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);
        $format        = strtolower($req->input('format', 'pdf'));

        $rows = EsiRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($salaryGroupId, fn($q) => $q->whereIn(
                'emp_id',
                \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ))
            ->orderBy('emp_id')
            ->get();

        $totals = [
            'gross' => $rows->sum('gross_wage'),
            'ee'    => $rows->sum('ee_0_75'),
            'er'    => $rows->sum('er_3_25'),
            'total' => $rows->sum('total_contribution'),
        ];

        $company   = $this->companyForHeader($cid);
        $group     = $salaryGroupId ? \App\Models\SalaryGroup::find($salaryGroupId) : null;
        $monthName = \DateTime::createFromFormat('!m', $month)->format('F');

        // CSV / Excel — flat ESIC upload schema (8 columns)
        if (in_array($format, ['csv', 'xls'], true)) {
            $headers = [
                'IP Number', 'IP Name', 'Payble Days', 'Total Monthly Wages',
                'Reason Code(Zero Working Days)', 'Last Working Day',
                'S. Group', 'Company',
            ];

            $empIds = $rows->pluck('emp_id')->all();
            $emps   = \App\Models\Employee::with(['salary_group','company'])
                ->whereIn('emp_id', $empIds)->get()->keyBy('emp_id');

            $data = [];
            foreach ($rows as $r) {
                $e = $emps->get($r->emp_id);
                $exitDate = ($e && !empty($e->date_of_relieving))
                    ? \Carbon\Carbon::parse($e->date_of_relieving)->format('d/m/Y')
                    : '';
                $data[] = [
                    $r->ip_no ?? ($e->esi_ip_no ?? ''),
                    $r->member_name ?? ($e->full_name ?? ''),
                    (int) round((float) ($r->days_worked ?? 0)),
                    (int) round((float) ($r->gross_wage ?? 0)),
                    '-----',
                    $exitDate,
                    $e->salary_group->salary_group_name ?? '',
                    $e->company->company_code ?? ($company->company_code ?? ''),
                ];
            }

            $stem = 'esi-' . strtolower($monthName) . '-' . $year
                  . ($salaryGroupId && $group ? '-' . preg_replace('/[^A-Za-z0-9]+/', '-', $group->salary_group_name) : '');
            return $format === 'csv'
                ? $this->streamCsv("{$stem}.csv", $headers, $data)
                : $this->streamXls("{$stem}.xls", "ESI — {$monthName} {$year}", $headers, $data);
        }

        return view('statutory.esi-challan-pdf', compact(
            'rows', 'totals', 'year', 'month', 'monthName', 'company', 'group'
        ));
    }

    public function ptPdf(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);
        $format        = strtolower($req->input('format', 'pdf'));

        $rows = PtRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($salaryGroupId, fn($q) => $q->whereIn(
                'emp_id',
                \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ))
            ->orderBy('emp_id')
            ->get();
        $total = $rows->sum('pt_amount');

        $company   = $this->companyForHeader($cid);
        $group     = $salaryGroupId ? \App\Models\SalaryGroup::find($salaryGroupId) : null;
        $monthName = \DateTime::createFromFormat('!m', $month)->format('F');

        if (in_array($format, ['csv', 'xls'], true)) {
            $headers = ['Emp ID', 'Employee Name', 'PAN', 'State', 'Slab Applied',
                        'PT Amount', 'Status', 'Salary Group', 'Company'];
            $empIds = $rows->pluck('emp_id')->all();
            $emps   = \App\Models\Employee::with(['salary_group','company'])
                ->whereIn('emp_id', $empIds)->get()->keyBy('emp_id');
            $data = [];
            foreach ($rows as $r) {
                $e = $emps->get($r->emp_id);
                $data[] = [
                    $r->emp_id,
                    $r->employee_name ?? ($e->full_name ?? ''),
                    $e->pan_no ?? '',
                    $r->state ?? '',
                    $r->slab_applied ?? '',
                    (int) round((float) ($r->pt_amount ?? 0)),
                    $r->status ?? 'Generated',
                    $e->salary_group->salary_group_name ?? '',
                    $e->company->company_code ?? ($company->company_code ?? ''),
                ];
            }
            $stem = 'pt-' . strtolower($monthName) . '-' . $year
                  . ($salaryGroupId && $group ? '-' . preg_replace('/[^A-Za-z0-9]+/', '-', $group->salary_group_name) : '');
            return $format === 'csv'
                ? $this->streamCsv("{$stem}.csv", $headers, $data)
                : $this->streamXls("{$stem}.xls", "PT — {$monthName} {$year}", $headers, $data);
        }

        return view('statutory.pt-pdf', compact(
            'rows', 'total', 'year', 'month', 'monthName', 'company', 'group'
        ));
    }

    public function lwfPdf(Request $req)
    {
        $year          = (int) $req->input('year', now()->year);
        $half          = $req->input('half', now()->month <= 6 ? 'H1' : 'H2');
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);
        $format        = strtolower($req->input('format', 'pdf'));

        $rows = LwfRecord::where('period_year', $year)
            ->where('period_half', $half)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($salaryGroupId, fn($q) => $q->whereIn(
                'emp_id',
                \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ))
            ->orderBy('emp_id')
            ->get();

        $totals = [
            'ee'    => $rows->sum('employee_contribution'),
            'er'    => $rows->sum('employer_contribution'),
            'total' => $rows->sum('total_contribution'),
        ];

        $company = $this->companyForHeader($cid);
        $group   = $salaryGroupId ? \App\Models\SalaryGroup::find($salaryGroupId) : null;
        $halfLabel = $half === 'H1' ? 'H1 (Jan – Jun)' : 'H2 (Jul – Dec)';

        if (in_array($format, ['csv', 'xls'], true)) {
            $headers = ['Emp ID', 'Employee Name', 'State', 'EE Contribution',
                        'ER Contribution', 'Total', 'Status', 'Salary Group', 'Company'];
            $empIds = $rows->pluck('emp_id')->all();
            $emps   = \App\Models\Employee::with(['salary_group','company'])
                ->whereIn('emp_id', $empIds)->get()->keyBy('emp_id');
            $data = [];
            foreach ($rows as $r) {
                $e = $emps->get($r->emp_id);
                $data[] = [
                    $r->emp_id,
                    $r->employee_name ?? ($e->full_name ?? ''),
                    $r->state ?? '',
                    (int) round((float) ($r->employee_contribution ?? 0)),
                    (int) round((float) ($r->employer_contribution ?? 0)),
                    (int) round((float) ($r->total_contribution ?? 0)),
                    $r->status ?? 'Generated',
                    $e->salary_group->salary_group_name ?? '',
                    $e->company->company_code ?? ($company->company_code ?? ''),
                ];
            }
            $stem = "lwf-{$year}-{$half}"
                  . ($salaryGroupId && $group ? '-' . preg_replace('/[^A-Za-z0-9]+/', '-', $group->salary_group_name) : '');
            return $format === 'csv'
                ? $this->streamCsv("{$stem}.csv", $headers, $data)
                : $this->streamXls("{$stem}.xls", "LWF — {$halfLabel} {$year}", $headers, $data);
        }

        return view('statutory.lwf-pdf', compact(
            'rows', 'totals', 'year', 'half', 'halfLabel', 'company', 'group'
        ));
    }

    public function tdsPdf(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);
        $format        = strtolower($req->input('format', 'pdf'));

        $rows = TdsRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($salaryGroupId, fn($q) => $q->whereIn(
                'emp_id',
                \App\Models\Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ))
            ->orderBy('emp_id')
            ->get();

        $totals = [
            'gross'   => $rows->sum('annual_gross'),
            'tax'     => $rows->sum('total_annual_tax'),
            'monthly' => $rows->sum('monthly_tds_for_period'),
        ];

        $company   = $this->companyForHeader($cid);
        $group     = $salaryGroupId ? \App\Models\SalaryGroup::find($salaryGroupId) : null;
        $monthName = \DateTime::createFromFormat('!m', $month)->format('F');

        if (in_array($format, ['csv', 'xls'], true)) {
            $headers = ['Emp ID', 'Employee Name', 'PAN', 'Regime',
                        'Annual Gross', 'Annual Tax', 'Monthly TDS',
                        'Section', 'Salary Group', 'Company'];
            $empIds = $rows->pluck('emp_id')->all();
            $emps   = \App\Models\Employee::with(['salary_group','company'])
                ->whereIn('emp_id', $empIds)->get()->keyBy('emp_id');
            $data = [];
            foreach ($rows as $r) {
                $e = $emps->get($r->emp_id);
                $data[] = [
                    $r->emp_id,
                    $r->employee_name ?? ($e->full_name ?? ''),
                    $r->pan ?? ($e->pan_no ?? ''),
                    $r->regime ?? 'New',
                    (int) round((float) ($r->annual_gross ?? 0)),
                    (int) round((float) ($r->total_annual_tax ?? 0)),
                    (int) round((float) ($r->monthly_tds_for_period ?? 0)),
                    '192',
                    $e->salary_group->salary_group_name ?? '',
                    $e->company->company_code ?? ($company->company_code ?? ''),
                ];
            }
            $stem = 'tds-' . strtolower($monthName) . '-' . $year
                  . ($salaryGroupId && $group ? '-' . preg_replace('/[^A-Za-z0-9]+/', '-', $group->salary_group_name) : '');
            return $format === 'csv'
                ? $this->streamCsv("{$stem}.csv", $headers, $data)
                : $this->streamXls("{$stem}.xls", "TDS — {$monthName} {$year}", $headers, $data);
        }

        return view('statutory.tds-pdf', compact(
            'rows', 'totals', 'year', 'month', 'monthName', 'company', 'group'
        ));
    }

    /**
     * Stream a CSV download. Adds a UTF-8 BOM so Excel opens Unicode names
     * (Hindi / accented chars) correctly.
     */
    protected function streamCsv(string $filename, array $headers, array $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $h = fopen('php://output', 'w');
            fwrite($h, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($h, $headers);
            foreach ($rows as $r) fputcsv($h, $r);
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Stream an Excel-compatible HTML table (HTML-as-XLS). No library dependency
     * required — opens cleanly in Excel, Google Sheets, and Numbers. Same
     * pattern used elsewhere in this app for payroll exports.
     */
    protected function streamXls(string $filename, string $title, array $headers, array $rows)
    {
        $html  = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel'>";
        $html .= "<head><meta charset='UTF-8'><title>" . e($title) . "</title>";
        $html .= "<style>body{font-family:Calibri,Arial} table{border-collapse:collapse} ";
        $html .= "td,th{border:1px solid #888;padding:4px 6px;font-size:11px} ";
        $html .= "th{background:#E5E7EB;font-weight:bold} .num{mso-number-format:'#\\,##0';text-align:right}</style></head><body>";
        $html .= "<h3>" . e($title) . "</h3><table><thead><tr>";
        foreach ($headers as $h) $html .= "<th>" . e($h) . "</th>";
        $html .= "</tr></thead><tbody>";
        foreach ($rows as $r) {
            $html .= "<tr>";
            foreach ($r as $cell) {
                $isNumeric = is_numeric($cell);
                $cls = $isNumeric ? " class='num'" : '';
                // Wrap longish strings as text to stop Excel auto-converting
                // PFNo / IP-Number / UAN long digit strings into scientific notation.
                if (!$isNumeric) {
                    $html .= "<td{$cls} style='mso-number-format:\"\\@\"'>" . e((string) $cell) . "</td>";
                } else {
                    $html .= "<td{$cls}>" . e((string) $cell) . "</td>";
                }
            }
            $html .= "</tr>";
        }
        $html .= "</tbody></table></body></html>";

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function form24q(Request $req)
    {
        $fy = $req->input('fy', '2025-26');
        // fy stored as integer (start year); accept "2025-26" or "2025"
        $fyInt = (int) substr((string) $fy, 0, 4);
        $records = Form24qRecord::where('fy', $fyInt)->get();
        return view('statutory.form24q', compact('records', 'fy'));
    }

    public function form16(Request $req, $empId)
    {
        $emp = Employee::find($empId);
        $fy  = $req->input('fy', '2025-26');
        return view('statutory.form16', compact('emp', 'fy'));
    }

    public function bonus(Request $req)
    {
        $fy    = $req->input('fy', '2025-26');
        $fyInt = (int) substr((string) $fy, 0, 4);
        $rows  = BonusProvision::where('fy', $fyInt)->get();
        return view('statutory.bonus', compact('rows', 'fy'));
    }

    public function gratuity(Request $req)
    {
        $employees = Employee::where('active_flag', true)->take(500)->get();
        $records = $employees->map(function ($e) {
            $doj   = $e->date_of_joining ? \Carbon\Carbon::parse($e->date_of_joining) : null;
            $years = $doj ? round(now()->diffInYears($doj), 2) : 0;
            $wage  = ($e->current_basic ?? 0) + ($e->current_da ?? 0);
            $eligible = $years >= 5;
            $amount   = $eligible ? round(($wage * 15 * $years) / 26, 2) : 0;
            return [
                'emp_id'   => $e->emp_id,
                'name'     => $e->full_name,
                'doj'      => $e->date_of_joining,
                'years'    => $years,
                'eligible' => $eligible,
                'amount'   => $amount,
            ];
        });
        return view('statutory.gratuity', compact('records'));
    }

    public function posh()
    {
        return view('statutory.posh');
    }

    public function calendar()
    {
        $tasks = [
            ['07 of next month',  'TDS Payment',          'IT Act §200',          'Monthly',     'Finance', 'ok'],
            ['15 of next month',  'EPF / ECR Filing',     'EPF Act 1952',         'Monthly',     'HR',      'ok'],
            ['15 of next month',  'ESIC Contribution',    'ESI Act 1948 §39',     'Monthly',     'HR',      'ok'],
            ['21 of next month',  'PT Maharashtra',       'MH PT Act 1975',       'Monthly',     'HR',      'warn'],
            ['31 May',            'Form 24Q (Q4)',        'IT Act',               'Quarterly',   'Finance', 'warn'],
            ['15 Jun',            'Form 16 Issue',        'IT Act §203',          'Annual',      'HR',      'warn'],
            ['31 Jul',            'LWF (MH)',             'MH LWF Act',           'Half-yearly', 'HR',      'ok'],
            ['30 Sep',            'Tax Audit (3CD)',      'IT Act §44AB',         'Annual',      'Finance', 'ok'],
            ['31 Jan',            'POSH §22 Annual Report','SHW Act 2013',         'Annual',      'HR',      'ok'],
            ['Annual',            'Bonus Disbursement',   'Payment of Bonus Act 1965', 'Annual', 'HR',      'warn'],
            ['Annual',            'Gratuity Provision',   'Payment of Gratuity Act 1972', 'Annual', 'Finance','ok'],
        ];
        return view('statutory.calendar', compact('tasks'));
    }
}
