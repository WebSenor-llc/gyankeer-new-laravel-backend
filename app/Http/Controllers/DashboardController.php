<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\AttendanceDaily;
use App\Models\SalaryRun;
use App\Models\Department;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $cid   = (int) session('active_company_id', 0);

        // Filter every query by the active company (when one is selected)
        $empQ = Employee::query();
        $attQ = AttendanceDaily::where('attn_date', $today);
        $runQ = SalaryRun::query();
        $deptQ = Department::query();
        $deptEmpScope = function ($q) use ($cid) {
            $q->where('active_flag', true);
            if ($cid) $q->where('company_id', $cid);
        };

        if ($cid) {
            $empQ->where('company_id', $cid);
            $attQ->where('company_id', $cid);
            $runQ->where('company_id', $cid);
            $deptQ->where('company_id', $cid);
        }

        $kpis = [
            'headcount' => (clone $empQ)->where('active_flag', true)->count(),
            'present'   => (clone $attQ)->where('status', 'Present')->count(),
            'on_leave'  => (clone $attQ)->where('status', 'On Leave')->count(),
            'on_duty'   => (clone $attQ)->where('status', 'On Duty')->count(),
            'absent'    => (clone $attQ)->where('status', 'Absent')->count(),
            'mismatch'  => (clone $attQ)->where('status', 'MissMatch')->count(),
        ];

        $latestRun     = (clone $runQ)->orderByDesc('period_year')->orderByDesc('period_month')->first();
        $payrollByDept = $deptQ->withSum(['employees as gross_sum' => $deptEmpScope], 'current_gross')->get();

        return view('dashboard.index', [
            'kpis'                  => $kpis,
            'latest_run'            => $latestRun,
            'payroll_by_dept'       => $payrollByDept,
            'employees_by_year'     => $this->headcountTrend($cid),
            'employees_by_category' => $this->categoryBreakdown($cid),
        ]);
    }

    private function headcountTrend(int $cid): array
    {
        $q = Employee::selectRaw('YEAR(date_of_joining) as yr, COUNT(*) as joined');
        if ($cid) $q->where('company_id', $cid);
        return $q->groupBy('yr')->orderBy('yr')->pluck('joined', 'yr')->toArray();
    }

    private function categoryBreakdown(int $cid): array
    {
        $q = Employee::where('active_flag', true)->selectRaw('employee_type, COUNT(*) as c');
        if ($cid) $q->where('company_id', $cid);
        return $q->groupBy('employee_type')->pluck('c', 'employee_type')->toArray();
    }
}
