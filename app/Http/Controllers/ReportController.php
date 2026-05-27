<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\SalaryRun;
use App\Models\SalaryTransaction;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function period(Request $req): array
    {
        return [
            (int) $req->input('year',  now()->year),
            (int) $req->input('month', now()->month),
        ];
    }

    public function salarySheet(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid = (int) session('active_company_id', 0);
        $q = Payslip::where('period_year', $year)->where('period_month', $month);
        if ($cid) {
            $q->whereIn('run_id', SalaryRun::where('company_id', $cid)->pluck('run_id'));
        }
        $rows = $q->paginate(50);
        $totals = [
            'gross' => $rows->sum('gross_earnings'),
            'net'   => $rows->sum('net_pay'),
            'count' => $rows->total(),
        ];
        return view('reports.salary-sheet', compact('rows', 'year', 'month', 'totals'));
    }

    public function salarySlip(Request $req)
    {
        [$year, $month] = $this->period($req);
        $empId = $req->input('emp_id');
        $payslip = $empId ? Payslip::where('emp_id', $empId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first() : null;
        $employees = Employee::where('active_flag', true)->get();
        return view('reports.salary-slip', compact('payslip', 'year', 'month', 'employees', 'empId'));
    }

    public function salarySlipPDF(Request $req)
    {
        return back()->with('status', 'PDF generation requires the laravel-dompdf package.');
    }

    public function hrLetters(Request $req)
    {
        $cid = (int) session('active_company_id', 0);
        $q = Employee::with('designation')->where('active_flag', true);
        if ($cid) $q->where('company_id', $cid);

        if ($s = trim((string) $req->input('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('full_name', 'like', "%{$s}%")
                  ->orWhere('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('emp_code', 'like', "%{$s}%");
            });
        }
        $employees = $q->orderBy('emp_id')->paginate(50)->withQueryString();
        $letterTypes = $this->hrLetterTypes();
        return view('reports.hr-letters', compact('employees', 'letterTypes'));
    }

    /** Letter type slug => display label. */
    private function hrLetterTypes(): array
    {
        return [
            'offer'        => 'Offer Letter',
            'appointment'  => 'Appointment Letter',
            'confirmation' => 'Confirmation Letter',
            'experience'   => 'Experience Letter',
            'relieving'    => 'Relieving Letter',
            'nda'          => 'NDA',
        ];
    }

    /**
     * Stream a Word (.doc) HR letter for a single employee.
     * Uses HTML wrapped in MS Word XML headers — opens natively in Word/LibreOffice.
     */
    public function hrLetterDownload(Request $req, $empId, $type)
    {
        $types = $this->hrLetterTypes();
        abort_unless(array_key_exists($type, $types), 404, 'Unknown letter type.');

        $employee = Employee::with(['company', 'designation', 'department'])
            ->where('emp_id', $empId)
            ->firstOrFail();

        $today = now();
        $body  = view("reports.hr-letters._{$type}", [
            'e'       => $employee,
            'c'       => $employee->company,
            'today'   => $today,
            'title'   => $types[$type],
        ])->render();

        $html = view('reports.hr-letters._wrapper', [
            'title' => $types[$type] . ' - ' . ($employee->full_name ?: $employee->emp_code),
            'body'  => $body,
        ])->render();

        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_',
            ($employee->emp_code ?: $employee->emp_id) . '_' . ($employee->full_name ?: 'employee') . '_' . $type);
        $filename = "{$slug}.doc";

        return response($html, 200, [
            'Content-Type'        => 'application/msword; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ]);
    }

    public function bankSheet(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid = (int) session('active_company_id', 0);
        $q = Payslip::where('period_year', $year)->where('period_month', $month);
        if ($cid) {
            $q->whereIn('run_id', SalaryRun::where('company_id', $cid)->pluck('run_id'));
        }
        $rows  = $q->paginate(50);
        $total = $rows->sum('net_pay');
        return view('reports.bank-sheet', compact('rows', 'year', 'month', 'total'));
    }

    public function incrementReport(Request $req)
    {
        $employees = Employee::whereNotNull('last_increment_date')
            ->orderByDesc('last_increment_date')
            ->paginate(50);
        return view('reports.increment', compact('employees'));
    }

    public function headcount(Request $req)
    {
        $cid = (int) session('active_company_id', 0);
        $base = Employee::where('active_flag', true);
        if ($cid) $base->where('company_id', $cid);

        $byDept = (clone $base)->selectRaw('dept_id, COUNT(*) as c')->groupBy('dept_id')->get();
        $byType = (clone $base)->selectRaw('employee_type, COUNT(*) as c')->groupBy('employee_type')->pluck('c', 'employee_type');
        $total  = (clone $base)->count();
        return view('reports.headcount', compact('byDept', 'byType', 'total'));
    }

    public function exit(Request $req)
    {
        $cid = (int) session('active_company_id', 0);
        $q = Employee::where('employment_status', 'Exited');
        if ($cid) $q->where('company_id', $cid);
        $rows = $q->paginate(50);
        return view('reports.exit', compact('rows'));
    }

    /**
     * Complete salary sheet — every payslip column with formula headers.
     * Joins payslips with employees so master data (group / dept) is shown.
     */
    public function completeSalary(Request $req)
    {
        [$year, $month] = $this->period($req);
        $cid = (int) session('active_company_id', 0);

        $runIds = SalaryRun::when($cid, fn($q) => $q->where('company_id', $cid))
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->pluck('run_id');

        $payslips = Payslip::whereIn('run_id', $runIds)->get()->keyBy('emp_id');

        $employees = Employee::with(['department', 'designation', 'salary_group'])
            ->where('active_flag', true)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->orderBy('emp_id')
            ->get();

        $rows = $employees->map(function ($e) use ($payslips) {
            $p = $payslips->get($e->emp_id);
            return [
                'emp'      => $e,
                'payslip'  => $p,
                'has'      => $p !== null,
            ];
        });

        // Aggregate totals for the totals row
        $totals = [
            'basic' => 0, 'hra' => 0, 'da' => 0, 'conv' => 0, 'med' => 0, 'spl' => 0,
            'bonus' => 0, 'arrear' => 0, 'ot' => 0,
            'gross' => 0,
            'epf_emp' => 0, 'vpf' => 0, 'esi_emp' => 0, 'pt' => 0, 'lwf_emp' => 0, 'tds' => 0,
            'loan' => 0, 'advance' => 0, 'fine' => 0,
            'total_ded' => 0, 'net' => 0,
            'epf_er' => 0, 'eps' => 0, 'edli' => 0, 'admin' => 0, 'esi_er' => 0,
            'gratuity' => 0, 'ctc' => 0,
        ];
        foreach ($rows as $r) {
            $p = $r['payslip'];
            if (!$p) continue;
            $totals['basic']   += $p->basic ?? 0;
            $totals['hra']     += $p->hra ?? 0;
            $totals['da']      += $p->da ?? 0;
            $totals['conv']    += is_numeric($p->conveyance) ? (float) $p->conveyance : 0;
            $totals['med']     += is_numeric($p->medical) ? (float) $p->medical : 0;
            $totals['spl']     += $p->spl_allow ?? 0;
            $totals['bonus']   += $p->bonus ?? 0;
            $totals['arrear']  += $p->arrear ?? 0;
            $totals['ot']      += $p->ot_amount ?? 0;
            $totals['gross']   += $p->gross_earnings ?? 0;
            $totals['epf_emp'] += $p->epf_emp ?? 0;
            $totals['vpf']     += $p->vpf ?? 0;
            $totals['esi_emp'] += $p->esi_emp ?? 0;
            $totals['pt']      += $p->pt ?? 0;
            $totals['lwf_emp'] += $p->lwf_emp ?? 0;
            $totals['tds']     += $p->tds ?? 0;
            $totals['loan']    += $p->loan_emi ?? 0;
            $totals['advance'] += $p->advance_recovery ?? 0;
            $totals['fine']    += $p->fine_recovery ?? 0;
            $totals['total_ded'] += $p->total_deductions ?? 0;
            $totals['net']     += $p->net_pay ?? 0;
            $totals['epf_er']  += $p->employer_pf ?? 0;
            $totals['eps']     += $p->eps ?? 0;
            $totals['edli']    += $p->edli ?? 0;
            $totals['admin']   += $p->pf_admin ?? 0;
            $totals['esi_er']  += $p->employer_esi ?? 0;
            $totals['gratuity']+= $p->gratuity_provision ?? 0;
            $totals['ctc']     += $p->total_employer_cost ?? 0;
        }

        // CSV export — same data, streamed as a downloadable file
        if ($req->input('export') === 'csv') {
            return $this->completeSalaryCsv($rows, $year, $month, $totals);
        }

        return view('reports.complete-salary', compact('rows', 'year', 'month', 'totals'));
    }

    /**
     * Stream the Complete Salary report as a CSV file.
     */
    protected function completeSalaryCsv($rows, int $year, int $month, array $totals)
    {
        $monthName = \DateTime::createFromFormat('!m', $month)->format('M');
        $filename  = "complete-salary-{$monthName}-{$year}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ];

        $columns = [
            'Emp ID', 'Name', 'Department', 'Designation', 'Salary Group',
            'Bank', 'Account No', 'IFSC', 'PAN', 'UAN',
            'Payable Days', 'LOP Days', 'Present Days',
            'Basic', 'HRA', 'DA', 'Conv', 'Medical', 'Spl Allow',
            'Bonus', 'Arrear', 'OT Amount', 'Gross',
            'EPF (Emp)', 'VPF', 'ESI (Emp)', 'PT', 'LWF (Emp)', 'TDS',
            'Loan EMI', 'Advance Recovery', 'Fine', 'Post Deduction',
            'Total Deductions', 'Net Pay',
            'EPF (Er)', 'EPS', 'EDLI', 'PF Admin', 'ESI (Er)',
            'Gratuity Provision', 'LWF (Er)', 'CTC',
            'Disbursement Mode', 'Disbursement Status', 'UTR No', 'Disb. Date',
        ];

        return response()->streamDownload(function () use ($rows, $columns, $totals) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns);

            foreach ($rows as $r) {
                $e = $r['emp'];
                $p = $r['payslip'];
                fputcsv($out, [
                    $e->emp_id,
                    $e->full_name,
                    $e->department->dept_name      ?? '',
                    $e->designation->designation_name ?? '',
                    $e->salary_group->salary_group_name ?? '',
                    $p->bank?->bank_name           ?? '',
                    $p->bank_account               ?? '',
                    $p->ifsc                       ?? '',
                    $e->pan                        ?? '',
                    $e->uan                        ?? '',
                    $p->payable_days               ?? '',
                    $p->lop_days                   ?? 0,
                    $p->present_days               ?? '',
                    $p->basic                      ?? 0,
                    $p->hra                        ?? 0,
                    $p->da                         ?? 0,
                    $p->conveyance                 ?? 0,
                    $p->medical                    ?? 0,
                    $p->spl_allow                  ?? 0,
                    $p->bonus                      ?? 0,
                    $p->arrear                     ?? 0,
                    $p->ot_amount                  ?? 0,
                    $p->gross_earnings             ?? 0,
                    $p->epf_emp                    ?? 0,
                    $p->vpf                        ?? 0,
                    $p->esi_emp                    ?? 0,
                    $p->pt                         ?? 0,
                    $p->lwf_emp                    ?? 0,
                    $p->tds                        ?? 0,
                    $p->loan_emi                   ?? 0,
                    $p->advance_recovery           ?? 0,
                    $p->fine_recovery              ?? 0,
                    $p->post_deduction             ?? 0,
                    $p->total_deductions           ?? 0,
                    $p->net_pay                    ?? 0,
                    $p->employer_pf                ?? 0,
                    $p->eps                        ?? 0,
                    $p->edli                       ?? 0,
                    $p->pf_admin                   ?? 0,
                    $p->employer_esi               ?? 0,
                    $p->gratuity_provision         ?? 0,
                    $p->lwf_employer               ?? 0,
                    $p->total_employer_cost        ?? 0,
                    $p->disbursement_mode          ?? '',
                    $p->disbursement_status        ?? '',
                    $p->utr_no                     ?? '',
                    $p->disbursement_date          ?? '',
                ]);
            }

            // Totals row
            fputcsv($out, [
                '', 'TOTALS', '', '', '', '', '', '', '', '',
                '', '', '',
                $totals['basic'], $totals['hra'], $totals['da'], $totals['conv'], $totals['med'], $totals['spl'],
                $totals['bonus'], $totals['arrear'], $totals['ot'], $totals['gross'],
                $totals['epf_emp'], $totals['vpf'], $totals['esi_emp'], $totals['pt'], $totals['lwf_emp'], $totals['tds'],
                $totals['loan'], $totals['advance'], $totals['fine'], '',
                $totals['total_ded'], $totals['net'],
                $totals['epf_er'], $totals['eps'], $totals['edli'], $totals['admin'], $totals['esi_er'],
                $totals['gratuity'], '', $totals['ctc'],
                '', '', '', '',
            ]);

            fclose($out);
        }, $filename, $headers);
    }

    public function statistical(Request $req)
    {
        $totals = [
            'employees' => Employee::where('active_flag', true)->count(),
            'payslips'  => Payslip::count(),
            'runs'      => SalaryRun::count(),
            'txns'      => SalaryTransaction::count(),
        ];
        return view('reports.statistical', compact('totals'));
    }
}
