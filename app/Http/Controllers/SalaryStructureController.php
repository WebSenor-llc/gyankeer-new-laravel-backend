<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryGroup;
use Illuminate\Http\Request;

/**
 * SUGAM HR-style "Manage Salary" — listing of all employees with their salary
 * configuration, and a per-employee "Employee Salary Configuration" form.
 *
 *   GET  /payroll/manage-salary                → index()      list view
 *   GET  /payroll/manage-salary/{empId}/config → configForm() per-employee config
 *   POST /payroll/manage-salary/{empId}        → save()
 */
class SalaryStructureController extends Controller
{
    public function index(Request $req)
    {
        $cid = (int) session('active_company_id', 0);
        $q = Employee::with(['department','designation','salary_group'])
            ->where('active_flag', true);
        if ($cid) $q->where('company_id', $cid);

        if ($req->filled('search_q')) {
            $sq = $req->search_q;
            $q->where(function ($w) use ($sq) {
                $w->where('emp_id', 'like', "%$sq%")
                  ->orWhere('full_name', 'like', "%$sq%")
                  ->orWhere('third_party_code', 'like', "%$sq%")
                  ->orWhere('fathers_name', 'like', "%$sq%");
            });
        }
        if ($req->filled('dept_id'))         $q->where('dept_id', $req->dept_id);
        if ($req->filled('salary_group_id')) $q->where('salary_group_id', $req->salary_group_id);
        if ($req->filled('emp_type'))        $q->where('employee_type', $req->emp_type);

        $format = $req->input('export');
        if (in_array($format, ['csv', 'xls'], true)) {
            return $this->export($format, (clone $q)->orderBy('emp_id')->get());
        }

        $employees = $q->orderBy('emp_id')->paginate(50)->appends($req->query());

        $departments  = Department::when($cid, fn($x) => $x->where('company_id', $cid))->orderBy('dept_name')->get();
        $salaryGroups = SalaryGroup::when($cid, fn($x) => $x->where('company_id', $cid))->orderBy('salary_group_name')->get();

        return view('payroll.manage-salary.index', compact('employees','departments','salaryGroups'));
    }

    /**
     * CSV / Excel export of the filtered employee salary configuration list.
     * Uses HTML-as-XLS trick (same as StatutoryController / LeaveBalanceController) — no extra package.
     */
    protected function export(string $format, $employees)
    {
        $headers = [
            'Emp ID','T.P. Code','Name','Father Name','Type','DOB','DOJ',
            'Designation','Salary Group','Department',
            'UAN','EPF Member ID','ESI IP No','Bank A/C','IFSC',
            'Basic','DA','HRA','Conveyance','Medical','Special/Other','Gross',
        ];

        $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d-M-Y') : '';

        $data = $employees->map(fn($e) => [
            $e->emp_id,
            $e->third_party_code,
            $e->full_name,
            $e->fathers_name,
            $e->employee_type,
            $fmtDate($e->dob),
            $fmtDate($e->date_of_joining),
            $e->designation->designation_name ?? '',
            $e->salary_group->salary_group_name ?? '',
            $e->department->dept_name ?? '',
            $e->uan,
            $e->epf_member_id,
            $e->esi_ip_no,
            $e->bank_account_no,
            $e->bank_ifsc,
            (float)($e->current_basic ?? 0),
            (float)($e->current_da    ?? 0),
            (float)($e->current_hra   ?? 0),
            (float)($e->current_conv  ?? 0),
            (float)($e->current_med   ?? 0),
            (float)($e->current_spl   ?? 0),
            (float)($e->current_gross ?? 0),
        ])->all();

        $stem = 'manage-salary-' . date('Y-m-d');

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
        $html .= "<head><meta charset='UTF-8'><title>Manage Salary</title>";
        $html .= "<style>td,th{border:1px solid #888;padding:4px 6px;font-size:11px} th{background:#E5E7EB;font-weight:bold} td.num{text-align:right}</style></head><body>";
        $html .= "<h3>Manage Salary — Per Employee Configuration</h3><table><thead><tr>";
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

    public function configForm($empId)
    {
        $emp = Employee::with(['department','designation','salary_group','bank'])
            ->where('emp_id', $empId)->firstOrFail();
        $cid = (int) session('active_company_id', 0);
        $salaryGroups = SalaryGroup::when($cid, fn($x) => $x->where('company_id', $cid))->orderBy('salary_group_name')->get();
        $banks        = Bank::orderBy('bank_name')->get();

        // List of generated payslip periods for this employee (for the download panel)
        $payslipPeriods = \App\Models\Payslip::where('emp_id', $emp->emp_id)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get(['payslip_id','period_year','period_month','gross_earnings','net_pay','disbursement_status']);

        return view('payroll.manage-salary.config', compact('emp','salaryGroups','banks','payslipPeriods'));
    }

    public function save($empId, Request $req)
    {
        $req->validate([
            'salary_group_id'        => 'nullable|integer',
            'pf_applicable_flag'     => 'nullable|boolean',
            'fpf_applicable_flag'    => 'nullable|boolean',
            'esi_applicable_flag'    => 'nullable|boolean',
            'co_applicable_flag'     => 'nullable|boolean',
            'overtime_applicable_flag' => 'nullable|boolean',
            'overtime_rate'          => 'nullable|numeric|min:0|max:5',
            'lwf_apply_flag'         => 'nullable|boolean',
            'ltc_entitled_flag'      => 'nullable|boolean',
            'auto_calc_flag'         => 'nullable|boolean',

            'epf_member_id'          => 'nullable|string|max:60',
            'esi_ip_no'              => 'nullable|string|max:30',
            'uan'                    => 'nullable|string|max:30',
            'group_gratuity_code'    => 'nullable|string|max:50',
            'bank_account_no'        => 'nullable|string|max:30',
            'bank_id'                => 'nullable|integer',
            'bank_ifsc'              => 'nullable|string|max:20',
            'payment_mode'           => 'nullable|string|max:30',

            'current_basic'          => 'nullable|numeric|min:0',
            'current_da'             => 'nullable|numeric|min:0',
            'current_hra'            => 'nullable|numeric|min:0',
            'current_conv'           => 'nullable|numeric|min:0',
            'current_med'            => 'nullable|numeric|min:0',
            'education_allow'        => 'nullable|numeric|min:0',
            'special_house_rent'     => 'nullable|numeric|min:0',
            'site_allowance'         => 'nullable|numeric|min:0',
            'sp_conv_petrol'         => 'nullable|numeric|min:0',
            'other_allowance'        => 'nullable|numeric|min:0',
            'deputation_allowance'   => 'nullable|numeric|min:0',
            'food_allowance'         => 'nullable|numeric|min:0',
            'city_allowance'         => 'nullable|numeric|min:0',
            'voucher_cash_allow'     => 'nullable|numeric|min:0',
            'kra_amount'             => 'nullable|numeric|min:0',
            'hard_duty_allow'        => 'nullable|numeric|min:0',

            'da_pct'                 => 'nullable|numeric|min:0|max:100',
            'hra_pct'                => 'nullable|numeric|min:0|max:100',
            'conv_pct'                => 'nullable|numeric|min:0|max:100',
            'medical_pct'            => 'nullable|numeric|min:0|max:100',
            'education_pct'          => 'nullable|numeric|min:0|max:100',
        ]);

        $emp = Employee::where('emp_id', $empId)->firstOrFail();

        $basic = (float) $req->input('current_basic', $emp->current_basic ?? 0);
        $auto  = $req->boolean('auto_calc_flag');

        // Auto-calculate components from Basic when auto_calc is on
        $da   = $auto ? round($basic * (float) $req->da_pct      / 100, 2) : (float) $req->input('current_da',   $emp->current_da ?? 0);
        $hra  = $auto ? round($basic * (float) $req->hra_pct     / 100, 2) : (float) $req->input('current_hra',  $emp->current_hra ?? 0);
        $conv = $auto ? round($basic * (float) $req->conv_pct    / 100, 2) : (float) $req->input('current_conv', $emp->current_conv ?? 0);
        $med  = $auto ? round($basic * (float) $req->medical_pct / 100, 2) : (float) $req->input('current_med',  $emp->current_med ?? 0);
        $edu  = $auto ? round($basic * (float) $req->education_pct / 100, 2) : (float) $req->input('education_allow', $emp->education_allow ?? 0);

        // Sum up all extra allowances into special_allowance bucket
        $extras = (float) $req->input('special_house_rent',0)
                + (float) $req->input('site_allowance',0)
                + (float) $req->input('sp_conv_petrol',0)
                + (float) $req->input('other_allowance',0)
                + (float) $req->input('deputation_allowance',0)
                + (float) $req->input('food_allowance',0)
                + (float) $req->input('city_allowance',0)
                + (float) $req->input('voucher_cash_allow',0)
                + (float) $req->input('kra_amount',0)
                + (float) $req->input('hard_duty_allow',0);

        $gross = $basic + $da + $hra + $conv + $med + $edu + $extras;

        $payload = [
            'salary_group_id'         => $req->salary_group_id ?: $emp->salary_group_id,
            'epf_member_id'           => $req->epf_member_id,
            'esi_ip_no'               => $req->esi_ip_no,
            'uan'                     => $req->uan,
            'bank_account_no'         => $req->bank_account_no,
            'bank_id'                 => $req->bank_id ?: null,
            'bank_ifsc'               => $req->bank_ifsc,

            'current_basic'           => $basic,
            'current_da'              => $da,
            'current_hra'             => $hra,
            'current_conv'            => $conv,
            'current_med'             => $med,
            'current_spl'             => $extras,    // bucket: all "extra" allowances
            'current_gross'           => $gross,
        ];

        // The new flag/numeric columns require migration 2026_05_04_000007.
        // If they don't exist, warn the user instead of silently dropping data.
        $newCols = ['pf_applicable_flag','fpf_applicable_flag','esi_applicable_flag','co_applicable_flag',
            'overtime_applicable_flag','overtime_rate','lwf_apply_flag','ltc_entitled_flag','group_gratuity_code',
            'payment_mode','auto_calc_flag','da_pct','hra_pct','conv_pct','medical_pct','education_pct',
            'special_house_rent','site_allowance','sp_conv_petrol','other_allowance','deputation_allowance',
            'food_allowance','city_allowance','voucher_cash_allow','kra_amount','hard_duty_allow','education_allow'];

        $boolCols = ['pf_applicable_flag','fpf_applicable_flag','esi_applicable_flag','co_applicable_flag',
            'overtime_applicable_flag','lwf_apply_flag','ltc_entitled_flag','auto_calc_flag'];

        $missingCols = [];
        foreach ($newCols as $c) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('employees', $c)) {
                if (in_array($c, $boolCols)) {
                    // Force-cast to int 0/1 so MySQL stores it cleanly even if the
                    // column is decimal/varchar instead of tinyint
                    $payload[$c] = $req->boolean($c) ? 1 : 0;
                } else {
                    $payload[$c] = $req->input($c);
                }
            } else {
                $missingCols[] = $c;
            }
        }

        $emp->update($payload);

        if (!empty($missingCols) && in_array('pf_applicable_flag', $missingCols)) {
            return redirect()->route('manage-salary.config', $emp->emp_id)
                ->with('status', '⚠️ Saved partial — but the salary-config columns (pf_applicable_flag, esi_applicable_flag, …) DO NOT EXIST in the employees table yet. Run: php artisan migrate. The PF/ESI checkbox states will not persist until you do.');
        }

        return redirect()->route('manage-salary.config', $emp->emp_id)
            ->with('status', "Saved salary configuration for {$emp->full_name}. Gross = ₹" . number_format($gross, 2));
    }
}
