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
        $rows = PfEcrRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->get();

        $totals = [
            'ee'    => $rows->sum('ee_share_12pct'),
            'eps'   => $rows->sum('eps_8_33'),
            'er'    => $rows->sum('er_share_3_67'),
            'edli'  => $rows->sum('edli_0_5'),
            'admin' => $rows->sum('pf_admin_0_5'),
        ];
        $totals['challan'] = array_sum($totals);

        return view('statutory.pf-challan', compact('rows', 'totals', 'year', 'month'));
    }

    public function generateEcr(Request $req)
    {
        return back()->with('status', 'ECR generation requires posted payroll runs for the period.');
    }

    public function esiChallan(Request $req)
    {
        [$year, $month] = $this->period($req);
        $rows = EsiRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->get();
        $totals = [
            'ee'    => $rows->sum('ee_0_75'),
            'er'    => $rows->sum('er_3_25'),
            'total' => $rows->sum('total_contribution'),
        ];
        return view('statutory.esi-challan', compact('rows', 'totals', 'year', 'month'));
    }

    public function pt(Request $req)
    {
        [$year, $month] = $this->period($req);
        $rows = PtRecord::where('period_year', $year)
            ->where('period_month', $month)
            ->get();
        $total = $rows->sum('pt_amount');
        return view('statutory.pt', compact('rows', 'year', 'month', 'total'));
    }

    public function lwf(Request $req)
    {
        $year = (int) $req->input('year', now()->year);
        $half = $req->input('half', now()->month <= 6 ? 'H1' : 'H2');
        $rows = LwfRecord::where('period_year', $year)
            ->where('period_half', $half)
            ->get();
        $total = $rows->sum('total_contribution');
        return view('statutory.lwf', compact('rows', 'year', 'half', 'total'));
    }

    public function tds(Request $req)
    {
        $employees = Employee::where('active_flag', true)->take(500)->get();
        $records = $employees->map(function ($e) {
            $annualGross = ($e->current_gross ?? 0) * 12;
            // Simplified estimate — real calc lives in TaxCalculator.
            $annualTax = max(0, ($annualGross - 750000) * 0.10);
            return [
                'emp_id'      => $e->emp_id,
                'name'        => $e->full_name,
                'pan'         => $e->pan_no,
                'regime'      => $e->tax_regime ?? 'New',
                'annual_gross'=> $annualGross,
                'annual_tax'  => $annualTax,
                'monthly_tds' => round($annualTax / 12),
            ];
        });
        return view('statutory.tds', compact('records'));
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
