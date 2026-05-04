<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\SalaryRun;
use Illuminate\Http\Request;

/**
 * Payslip viewer — list, individual view, and printable HTML.
 *
 *   GET /payroll/payslips                                → index()  list all
 *   GET /payroll/payslips/{empId}/{year}/{month}         → show()   individual
 *   GET /payroll/payslips/{empId}/{year}/{month}/print   → printable, no chrome
 */
class PayslipController extends Controller
{
    public function index(Request $req)
    {
        $cid   = (int) session('active_company_id', 0);
        $year  = (int) $req->input('year',  now()->year);
        $month = (int) $req->input('month', now()->month);

        $runIds = SalaryRun::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('period_year', $year)->where('period_month', $month)
            ->pluck('run_id');

        $q = Payslip::with('emp.department','emp.salary_group')->whereIn('run_id', $runIds);

        if ($req->filled('emp_id'))   $q->where('emp_id', $req->emp_id);
        if ($req->filled('group_id')) $q->whereHas('emp', fn($w) => $w->where('salary_group_id', $req->group_id));

        $payslips = $q->orderBy('emp_id')->paginate(50)->appends($req->query());

        $totals = [
            'count' => Payslip::whereIn('run_id', $runIds)->count(),
            'gross' => (float) Payslip::whereIn('run_id', $runIds)->sum('gross_earnings'),
            'net'   => (float) Payslip::whereIn('run_id', $runIds)->sum('net_pay'),
        ];

        $salaryGroups = \App\Models\SalaryGroup::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('salary_group_name')->get();

        return view('payroll.payslips.index', compact('payslips','year','month','totals','salaryGroups'));
    }

    public function show($empId, $year, $month)
    {
        $payslip = Payslip::with('emp.department','emp.designation','emp.salary_group','emp.bank','run')
            ->where('emp_id', $empId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->firstOrFail();

        $company = Company::find($payslip->emp->company_id);

        return view('payroll.payslips.show', compact('payslip','company'));
    }

    public function printable($empId, $year, $month)
    {
        $payslip = Payslip::with('emp.department','emp.designation','emp.salary_group','emp.bank','run')
            ->where('emp_id', $empId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->firstOrFail();

        $company = Company::find($payslip->emp->company_id);

        return view('payroll.payslips.printable', compact('payslip','company'));
    }
}
