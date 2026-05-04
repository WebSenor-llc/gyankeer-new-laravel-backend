<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\Payslip;
use Illuminate\Http\Request;

class ESSController extends Controller
{
    private function selfEmployee(Request $req): ?Employee
    {
        $empId = $req->input('emp_id') ?? auth()->user()?->emp_id;
        if ($empId) {
            return Employee::where('emp_id', $empId)->first();
        }
        return Employee::where('active_flag', true)->orderBy('emp_id')->first();
    }

    public function index(Request $req)
    {
        $emp = $this->selfEmployee($req);
        $latestPayslip = $emp ? Payslip::where('emp_id', $emp->emp_id)
            ->orderByDesc('period_year')->orderByDesc('period_month')
            ->first() : null;
        $leaveBalance = $emp ? LeaveBalance::where('emp_id', $emp->emp_id)->get() : collect();
        $recentLeaves = $emp ? LeaveApplication::where('emp_id', $emp->emp_id)
            ->orderByDesc('applied_at')
            ->take(5)->get() : collect();
        return view('ess.index', compact('emp', 'latestPayslip', 'leaveBalance', 'recentLeaves'));
    }

    public function payslip(Request $req)
    {
        $emp = $this->selfEmployee($req);
        $payslips = $emp ? Payslip::where('emp_id', $emp->emp_id)
            ->orderByDesc('period_year')->orderByDesc('period_month')->get() : collect();
        return view('ess.payslip', compact('emp', 'payslips'));
    }

    public function itDeclaration(Request $req)
    {
        $emp = $this->selfEmployee($req);
        return view('ess.it-decl', compact('emp'));
    }

    public function saveItDeclaration(Request $req)
    {
        return back()->with('status', 'IT declaration saved.');
    }

    public function form16(Request $req)
    {
        $emp = $this->selfEmployee($req);
        $fy  = $req->input('fy', '2025-26');
        return view('ess.form16', compact('emp', 'fy'));
    }
}
