<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Payslip;
use App\Models\SalaryRun;
use App\Services\PayrollEngine;
use Illuminate\Http\Request;

class SalaryRunController extends Controller
{
    public function index()
    {
        $cid  = (int) session('active_company_id', 0);
        $q    = SalaryRun::query();
        if ($cid) $q->where('company_id', $cid);
        $runs = $q->orderByDesc('period_year')->orderByDesc('period_month')->paginate(24);
        return view('payroll.runs', compact('runs'));
    }

    /**
     * SUGAM HR-style "Salary Generation" page.
     *   - Pick Company + Salary Group + Month
     *   - "Get List" → preview employees in that group
     *   - "Generate Salary" → run payroll engine for THOSE employees only
     */
    public function generate(Request $req)
    {
        $cid       = (int) session('active_company_id', 0);
        $companies = Company::orderBy('company_name')->get();
        $year      = (int) $req->input('year',  now()->year);
        $month     = (int) $req->input('month', now()->month);
        $companyId = (int) $req->input('company_id', $cid);

        // Multi-select: salary_group_ids[] (+ back-compat for old singular salary_group_id)
        $rawIds = $req->input('salary_group_ids', []);
        if (!is_array($rawIds)) $rawIds = [$rawIds];
        if ($req->filled('salary_group_id')) $rawIds[] = $req->input('salary_group_id');
        $salaryGroupIds = collect($rawIds)
            ->map(fn($v) => (int) $v)
            ->filter(fn($v) => $v > 0)
            ->unique()->values()->all();
        $salaryGroupId = $salaryGroupIds[0] ?? 0; // legacy var

        // Salary groups for the picked company (or all if none).
        // Also include legacy rows imported without company_id but linked via
        // under_company or existing employee assignments.
        $salaryGroups = $this->salaryGroupsForCompany($companyId);

        // Single-group export branch — kept compatible with existing per-group buttons
        if ($req->input('export') && $companyId && $req->filled('salary_group_id')) {
            $singleGid = (int) $req->input('salary_group_id');
            $exportEmps = \App\Models\Employee::with(['department','salary_group'])
                ->where('active_flag', true)
                ->where('company_id', $companyId)
                ->where('salary_group_id', $singleGid)
                ->when($req->input('selected_emp_ids'),
                       fn($q,$ids) => $q->whereIn('emp_id', (array) $ids))
                ->orderBy('emp_id')->get();
            if (in_array($req->input('export'), ['csv','xls'], true)) {
                return $this->exportGroupSheet($req->input('export'), $exportEmps, $companyId, $singleGid, $year, $month);
            }
            if ($req->input('export') === 'pdf') {
                return $this->exportGroupPdf($exportEmps, $companyId, $singleGid, $year, $month);
            }
            if ($req->input('export') === 'muster') {
                return $this->exportMusterRoll($exportEmps, $companyId, $singleGid, $year, $month);
            }
        }

        // Employee preview list (only when "Get List" was clicked)
        $employees     = collect();
        $previewLoaded = false;
        if ($req->boolean('get_list') && $companyId && count($salaryGroupIds) > 0) {
            $employees = \App\Models\Employee::with(['department','salary_group'])
                ->where('active_flag', true)
                ->where('company_id', $companyId)
                ->whereIn('salary_group_id', $salaryGroupIds)
                ->orderBy('emp_id')->get();
            $previewLoaded = true;
        }

        $employeesByGroup = $employees->groupBy('salary_group_id');
        $orderedGroups    = \App\Models\SalaryGroup::whereIn('salary_group_id', $salaryGroupIds)
            ->orderBy('salary_group_name')->get()->keyBy('salary_group_id');

        // Already-generated payslips for this period (so the UI can show "already generated" badges)
        $existingPayslipEmps = collect();
        if ($companyId) {
            $runIds = SalaryRun::where('company_id', $companyId)
                ->where('period_year', $year)->where('period_month', $month)
                ->pluck('run_id');
            $existingPayslipEmps = Payslip::whereIn('run_id', $runIds)->pluck('emp_id')->unique();
        }

        return view('payroll.generate', compact(
            'companies', 'salaryGroups', 'employees',
            'companyId', 'salaryGroupId', 'salaryGroupIds',
            'employeesByGroup', 'orderedGroups',
            'year', 'month',
            'previewLoaded', 'existingPayslipEmps'
        ));
    }

    /**
     * Resolve salary groups for a company, including legacy rows imported
     * without company_id but still tied to the company via `under_company`
     * or by employee assignment.
     */
    protected function salaryGroupsForCompany(int $companyId)
    {
        $query = \App\Models\SalaryGroup::query();

        if (!$companyId) {
            return $query->orderBy('salary_group_name')->get();
        }

        $groups = $query->where('company_id', $companyId)->get();

        $company = Company::find($companyId);
        if ($company && filled($company->company_name)) {
            $companyName = trim((string) $company->company_name);
            $groups = $groups->concat(
                \App\Models\SalaryGroup::query()
                    ->where('under_company', 'like', '%' . $companyName . '%')
                    ->get()
            );
        }

        $employeeGroupIds = \App\Models\Employee::where('company_id', $companyId)
            ->whereNotNull('salary_group_id')
            ->distinct()
            ->pluck('salary_group_id');

        if ($employeeGroupIds->isNotEmpty()) {
            $groups = $groups->concat(
                \App\Models\SalaryGroup::whereIn('salary_group_id', $employeeGroupIds)->get()
            );
        }

        return $groups->unique('salary_group_id')->sortBy('salary_group_name')->values();
    }

    /**
     * Export the salary sheet for the picked (company × group × period) as
     * CSV or HTML-as-XLS (no dependency required; opens cleanly in Excel,
     * Google Sheets, and Numbers).
     */
    protected function exportGroupSheet(string $format, $employees, int $companyId, int $salaryGroupId, int $year, int $month)
    {
        $monthName = \DateTime::createFromFormat('!m', $month)->format('M');
        // When no group picked, label the file "all-groups" instead of "group"
        $groupName = $salaryGroupId
            ? (\App\Models\SalaryGroup::where('salary_group_id', $salaryGroupId)->value('salary_group_name') ?? 'group')
            : 'all-groups';
        $slug      = preg_replace('/[^A-Za-z0-9]+/', '-', $groupName);
        $filename  = "salary-sheet-{$slug}-{$monthName}-{$year}." . ($format === 'csv' ? 'csv' : 'xls');

        // Pull payslips for this period × these employees (already-generated)
        $runIds = SalaryRun::where('company_id', $companyId)
            ->where('period_year', $year)->where('period_month', $month)->pluck('run_id');
        $empIds   = $employees->pluck('emp_id')->all();
        $payslips = Payslip::whereIn('run_id', $runIds)->whereIn('emp_id', $empIds)->get()->keyBy('emp_id');

        // Build rows in memory
        $columns = [
            'Emp ID','Name','Department','Salary Group',
            'Basic','HRA','DA','Conv','Medical','Spl','Bonus','Arrear','OT Amount',
            'Gross Earnings',
            'EPF (Emp)','VPF','ESI (Emp)','PT','LWF (Emp)','TDS','Loan EMI','Advance','Fine','Post Ded',
            'Total Deductions','Net Pay',
            'EPF (Er)','EPS','EDLI','PF Admin','ESI (Er)','Gratuity','LWF (Er)','CTC',
            'Bank A/C','IFSC','Status',
        ];

        $dataRows = [];
        $totals   = array_fill_keys(array_slice($columns, 4), 0);  // numeric columns only
        foreach ($employees as $e) {
            $p = $payslips->get($e->emp_id);
            $row = [
                $e->emp_id,
                $e->full_name,
                $e->department->dept_name              ?? '',
                $e->salary_group->salary_group_name    ?? '',
                $p->basic                              ?? 0,
                $p->hra                                ?? 0,
                $p->da                                 ?? 0,
                $p->conveyance                         ?? 0,
                $p->medical                            ?? 0,
                $p->spl_allow                          ?? 0,
                $p->bonus                              ?? 0,
                $p->arrear                             ?? 0,
                $p->ot_amount                          ?? 0,
                $p->gross_earnings                     ?? 0,
                $p->epf_emp                            ?? 0,
                $p->vpf                                ?? 0,
                $p->esi_emp                            ?? 0,
                $p->pt                                 ?? 0,
                $p->lwf_emp                            ?? 0,
                $p->tds                                ?? 0,
                $p->loan_emi                           ?? 0,
                $p->advance_recovery                   ?? 0,
                $p->fine_recovery                      ?? 0,
                $p->post_deduction                     ?? 0,
                $p->total_deductions                   ?? 0,
                $p->net_pay                            ?? 0,
                $p->employer_pf                        ?? 0,
                $p->eps                                ?? 0,
                $p->edli                               ?? 0,
                $p->pf_admin                           ?? 0,
                $p->employer_esi                       ?? 0,
                $p->gratuity_provision                 ?? 0,
                $p->lwf_employer                       ?? 0,
                $p->total_employer_cost                ?? 0,
                $e->bank_account_no                    ?? '',
                $e->bank_ifsc                          ?? '',
                $p ? 'GENERATED' : 'PENDING',
            ];
            $dataRows[] = $row;

            // Sum numeric columns (positions 4..33 — last 3 are text)
            for ($i = 4; $i <= 33; $i++) {
                $totals[$columns[$i]] += (float) ($row[$i] ?? 0);
            }
        }

        // Totals row — text in first 4 cols, numbers next, blanks last 3
        $totalsRow = ['', 'TOTALS', '', ''];
        for ($i = 4; $i <= 33; $i++) $totalsRow[] = $totals[$columns[$i]];
        $totalsRow = array_merge($totalsRow, ['', '', '']);

        if ($format === 'csv') {
            return $this->streamCsv($filename, $columns, $dataRows, $totalsRow);
        }
        return $this->streamHtmlXls($filename, $columns, $dataRows, $totalsRow, "Salary Sheet — {$groupName} — {$monthName} {$year}");
    }

    /**
     * Export the salary sheet as a SUGAM-style "Payment of Wages Register" PDF.
     * Returns a self-contained HTML page — Cmd+P / Ctrl+P → Save as PDF gives
     * the actual PDF file, no PhpSpreadsheet/dompdf dependency required.
     */
    protected function exportGroupPdf($employees, int $companyId, int $salaryGroupId, int $year, int $month)
    {
        $company   = \App\Models\Company::find($companyId);
        $group     = \App\Models\SalaryGroup::find($salaryGroupId);
        $monthName = \DateTime::createFromFormat('!m', $month)->format('M') . '-' . $year;
        $monthLabel= \DateTime::createFromFormat('!m', $month)->format('M') . '-' . $year;

        $runIds = SalaryRun::where('company_id', $companyId)
            ->where('period_year', $year)->where('period_month', $month)->pluck('run_id');
        $payslips = Payslip::whereIn('run_id', $runIds)
            ->whereIn('emp_id', $employees->pluck('emp_id'))
            ->get()->keyBy('emp_id');

        // Build rows with all required fields. Each item carries the 4-line
        // multi-cell groups as arrays so the Blade can render them via rowspan.
        $rows = [];
        $i = 0;
        $totals = [
            'wdays'=>0,'leaves'=>0,'ph'=>0,'wo'=>0,'abs'=>0,'pdays'=>0,
            'basic_da'=>0,'hra'=>0,'transport'=>0,'med'=>0,'uniform'=>0,'sphr'=>0,'basic_arr'=>0,
            'other_fixed'=>0,'oth_arr'=>0,'gross'=>0,
            'esi'=>0,'tds'=>0,'pf'=>0,'pf_arr'=>0,'vpf'=>0,
            'loan'=>0,'adv'=>0,'maint'=>0,'flat'=>0,
            'cant'=>0,'mobile'=>0,'rent'=>0,'wf'=>0,
            'pt'=>0,'ag'=>0,'misc'=>0,'lwf'=>0,
            'total_ded'=>0,'net'=>0,
        ];

        foreach ($employees as $e) {
            $i++;
            $p = $payslips->get($e->emp_id);

            // Read attendance summary if available so we can show W.Days/Leaves/etc.
            $att = null;
            if (\Illuminate\Support\Facades\Schema::hasTable('attendance_summary')) {
                $att = \App\Models\AttendanceSummary::where('emp_id', $e->emp_id)
                    ->where('period_year', $year)->where('period_month', $month)->first();
            }
            $wDays  = $att ? (float)$att->p_count : ($p->present_days ?? 0);
            $leaves = $att ? (float)($att->cl_count + $att->sl_count + $att->pl_count) : 0;
            $ph     = $att ? (float)($att->ph_count ?? 0) : 0;
            $abs    = $att ? (float)$att->a_count : 0;
            $wo     = $att ? (float)$att->w_count : 0;
            $pDays  = $p->payable_days ?? \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

            // Read manual deductions for the granular SUGAM deduction columns
            $manual = null;
            if (\Illuminate\Support\Facades\Schema::hasTable('manual_deductions')) {
                $manual = \App\Models\ManualDeduction::where('emp_id', $e->emp_id)
                    ->where('period_year', $year)->where('period_month', $month)->first();
            }

            $row = [
                'sr'           => $i,
                'emp_id'       => $e->emp_id,
                'name'         => $e->full_name,
                'father'       => $e->fathers_name ?? '',
                'designation'  => $e->designation->designation_name ?? '',
                'pf_no'        => $e->epf_member_id ?? '',
                'uan'          => $e->uan ?? '',
                'esi_no'       => $e->esi_ip_no ?? '',
                'pan'          => $e->pan_no ?? '',
                'wdays'        => $wDays,
                'leaves'       => $leaves,
                'ph'           => $ph,
                'wo'           => $wo,
                'abs'          => $abs,
                'pdays'        => $pDays,
                'basic_da'     => (float) (($p->basic ?? 0) + ($p->da ?? 0)),
                'hra'          => (float) ($p->hra ?? 0),
                'transport'    => (float) ($p->conveyance ?? 0),
                'med'          => (float) ($p->medical ?? 0),
                'uniform'      => 0,
                'sphr'         => (float) ($p->spl_allow ?? 0),
                'basic_arr'    => (float) ($p->arrear ?? 0),
                'other_fixed' => 0,
                'oth_arr'      => 0,
                'gross'        => (float) ($p->gross_earnings ?? 0),
                'esi'          => (float) ($p->esi_emp ?? 0),
                'tds'          => (float) ($p->tds ?? 0),
                'pf'           => (float) ($p->epf_emp ?? 0),
                'pf_arr'       => 0,
                'vpf'          => (float) ($p->vpf ?? 0),
                'loan'         => (float) ($p->loan_emi ?? 0),
                'adv'          => (float) ($p->advance_recovery ?? 0),
                'maint'        => $manual ? (float) $manual->maintenance_charge : 0,
                'flat'         => 0,
                'cant'         => $manual ? (float) $manual->canteen_deduction : 0,
                'mobile'       => $manual ? (float) $manual->mobile_deduction : 0,
                'rent'         => $manual ? (float) $manual->rent_meridian : 0,
                'wf'           => 0,
                'pt'           => (float) ($p->pt ?? 0),
                'ag'           => $manual ? (float) $manual->ag_donation : 0,
                'misc'         => ($manual ? (float) $manual->misc_deduction : 0),
                'lwf'          => (float) ($p->lwf_emp ?? 0),
                'total_ded'    => (float) ($p->total_deductions ?? 0),
                'net'          => (float) ($p->net_pay ?? 0),
                'bank_name'    => $e->bank->bank_name ?? '',
                'bank_branch'  => $e->bank_branch ?? ($e->bank->bank_name ?? ''),
                'bank_acno'    => $e->bank_account_no ?? '',
            ];

            // Money columns print as whole rupees per row (number_format(...,0)),
            // so accumulate the ROUNDED value to keep the footer total equal to the
            // sum of the printed rows. Attendance columns print at 2 decimals -> keep raw.
            $attnKeys = ['wdays','leaves','ph','wo','abs','pdays'];
            foreach (array_keys($totals) as $k) {
                $val = (float) $row[$k];
                $totals[$k] += in_array($k, $attnKeys, true) ? $val : round($val);
            }
            $rows[] = $row;
        }

        return view('payroll.generate-pdf', compact('rows', 'totals', 'company', 'group', 'year', 'month', 'monthLabel'));
    }

    /**
     * Attendance Muster Roll — month-wise daily grid (days 1..N) per employee
     * with a short status code per day, plus summary columns
     * (Presents / Week Off / Paid Holidays / Leaves / Absent+ESI / Payable Days).
     * Renders the same print-to-PDF Blade pattern used by exportGroupPdf().
     */
    protected function exportMusterRoll($employees, int $companyId, int $salaryGroupId, int $year, int $month)
    {
        $company    = \App\Models\Company::find($companyId);
        $group      = \App\Models\SalaryGroup::find($salaryGroupId);
        $monthLabel = \DateTime::createFromFormat('!m', $month)->format('M') . '-' . $year;
        $daysInMonth= \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

        // Day header labels with weekday short + Sunday flag (for highlight)
        $dayLabels = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $c = \Carbon\Carbon::createFromDate($year, $month, $d);
            $dayLabels[$d] = ['short' => $c->format('D'), 'is_sun' => $c->dayOfWeek === 0];
        }

        $empIds = $employees->pluck('emp_id');

        // Per-day attendance: emp_id => [day => short code]
        $daily = \App\Models\AttendanceDaily::whereIn('emp_id', $empIds)
            ->whereYear('attn_date', $year)->whereMonth('attn_date', $month)
            ->get();
        $cells = [];
        foreach ($daily as $row) {
            $day = (int) \Carbon\Carbon::parse($row->attn_date)->format('j');
            $cells[$row->emp_id][$day] = $this->musterCode($row->status, $row->status_reason);
        }

        // Authoritative summary counts per employee
        $summaries = \App\Models\AttendanceSummary::whereIn('emp_id', $empIds)
            ->where('period_year', $year)->where('period_month', $month)
            ->get()->keyBy('emp_id');

        $rows = [];
        $totals = ['present'=>0,'weekoff'=>0,'ph'=>0,'leaves'=>0,'absent'=>0,'payable'=>0];
        $sr = 0;
        foreach ($employees as $e) {
            $sr++;
            $s = $summaries->get($e->emp_id);
            $present = $s ? (float) $s->p_count : 0;
            $weekoff = $s ? (float) $s->w_count : 0;
            $ph      = $s ? (float) ($s->ph_count ?? 0) : 0;
            $leaves  = $s ? (float) ($s->cl_count + $s->sl_count + $s->pl_count) : 0;
            $absent  = $s ? (float) $s->a_count : 0;
            $payable = $daysInMonth - $absent;   // Days in month − Absent

            $rows[] = [
                'sr'          => $sr,
                'emp_id'      => $e->emp_id,
                'name'        => $e->full_name,
                'designation' => $e->designation->designation_name ?? '',
                'days'        => $cells[$e->emp_id] ?? [],
                'present'     => $present,
                'weekoff'     => $weekoff,
                'ph'          => $ph,
                'leaves'      => $leaves,
                'absent'      => $absent,
                'payable'     => $payable,
            ];

            $totals['present'] += $present;
            $totals['weekoff'] += $weekoff;
            $totals['ph']      += $ph;
            $totals['leaves']  += $leaves;
            $totals['absent']  += $absent;
            $totals['payable'] += $payable;
        }

        return view('payroll.muster-pdf', compact(
            'rows', 'totals', 'company', 'group', 'year', 'month',
            'monthLabel', 'dayLabels', 'daysInMonth'
        ));
    }

    /** Map an attendance_daily status label (+leave reason) to a short muster code. */
    protected function musterCode(?string $status, ?string $reason): string
    {
        $status = trim((string) $status);
        switch (strtolower($status)) {
            case 'present':      return 'P';
            case 'absent':       return 'A';
            case 'weekly off':   return 'WO';
            case 'holiday':      return 'PH';
            case 'on duty':      return 'OD';
            case 'half day':     return 'HD';
            case 'on leave':
                $r = strtoupper(trim((string) $reason));
                // status_reason may hold CL/SL/PL (sometimes "CL Half", "P/CL")
                if (str_contains($r, 'CL')) return 'CL';
                if (str_contains($r, 'SL')) return 'SL';
                if (str_contains($r, 'PL')) return 'PL';
                return 'L';
            default:
                return $status !== '' ? strtoupper(substr($status, 0, 2)) : '';
        }
    }

    protected function streamCsv(string $filename, array $columns, array $dataRows, array $totalsRow)
    {
        return response()->streamDownload(function () use ($columns, $dataRows, $totalsRow) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");                 // UTF-8 BOM for Excel
            fputcsv($out, $columns);
            foreach ($dataRows as $r) fputcsv($out, $r);
            fputcsv($out, $totalsRow);
            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ]);
    }

    /**
     * Stream an HTML-table-as-XLS — opens cleanly in Excel/Numbers/Google Sheets
     * without needing PhpSpreadsheet. Numeric cells get x:num="1" so Excel keeps
     * them as numbers (no quote-prefix string treatment).
     */
    protected function streamHtmlXls(string $filename, array $columns, array $dataRows, array $totalsRow, string $title)
    {
        return response()->streamDownload(function () use ($columns, $dataRows, $totalsRow, $title) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="UTF-8"><style>';
            echo 'body{font-family:Arial,sans-serif;font-size:11px}';
            echo 'table{border-collapse:collapse}';
            echo 'th{background:#FEF2F2;color:#991B1B;border:1px solid #999;padding:4px 6px;text-align:left}';
            echo 'td{border:1px solid #ccc;padding:3px 6px;vertical-align:top}';
            echo 'tr.totals td{background:#F1F5F9;font-weight:bold;border-top:2px solid #475569}';
            echo '.title{font-size:14px;font-weight:bold;color:#991B1B;padding:6px 0}';
            echo '</style></head><body>';
            echo '<div class="title">' . htmlspecialchars($title) . '</div>';
            echo '<table><thead><tr>';
            foreach ($columns as $c) echo '<th>' . htmlspecialchars($c) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($dataRows as $r) {
                echo '<tr>';
                foreach ($r as $i => $cell) {
                    if ($i >= 4 && $i <= 33 && is_numeric($cell)) {
                        echo '<td x:num="1" style="text-align:right">' . number_format((float)$cell, 2, '.', '') . '</td>';
                    } else {
                        echo '<td>' . htmlspecialchars((string) $cell) . '</td>';
                    }
                }
                echo '</tr>';
            }
            echo '<tr class="totals">';
            foreach ($totalsRow as $i => $cell) {
                if ($i >= 4 && $i <= 33 && is_numeric($cell)) {
                    echo '<td x:num="1" style="text-align:right">' . number_format((float)$cell, 2, '.', '') . '</td>';
                } else {
                    echo '<td>' . htmlspecialchars((string) $cell) . '</td>';
                }
            }
            echo '</tr></tbody></table></body></html>';
        }, $filename, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ]);
    }

    /**
     * Process the "Generate Salary" submit — creates (or reuses) the salary run
     * for the (company × period), then computes payslips ONLY for the selected
     * employees from the picked salary group.
     */
    /**
     * Hard-delete generated payslips for a (company × salary group × period).
     * Requires the logged-in user's password to confirm — defends against
     * accidental clicks. Attendance, manual_deductions, and overtime entries
     * are preserved (so the user can simply regenerate after deletion).
     */
    public function deleteGroupPayroll(Request $req)
    {
        $req->validate([
            'company_id'      => 'required|integer',
            'salary_group_id' => 'required|integer',
            'year'            => 'required|integer|between:2020,2030',
            'month'           => 'required|integer|between:1,12',
            'password'        => 'required|string',
        ]);

        // Verify password against the logged-in user
        $user = auth()->user();
        if (!$user || !\Illuminate\Support\Facades\Hash::check($req->password, $user->password)) {
            return back()->withInput()->with('status', '❌ Wrong password — payslips NOT deleted.');
        }

        $g = \App\Models\SalaryGroup::find($req->integer('salary_group_id'));
        if (!$g) return back()->with('status', 'Salary group not found.');

        $empIds = \App\Models\Employee::where('salary_group_id', $g->salary_group_id)
            ->where('company_id', $req->integer('company_id'))
            ->pluck('emp_id');

        $cnt = Payslip::withTrashed()
            ->whereIn('emp_id', $empIds)
            ->where('period_year',  $req->integer('year'))
            ->where('period_month', $req->integer('month'))
            ->count();

        if ($cnt === 0) {
            return back()->with('status', "No payslips to delete for {$g->salary_group_name} ({$req->month}/{$req->year}).");
        }

        Payslip::withTrashed()
            ->whereIn('emp_id', $empIds)
            ->where('period_year',  $req->integer('year'))
            ->where('period_month', $req->integer('month'))
            ->forceDelete();

        return redirect()->route('payroll.generate', [
                'company_id'       => $req->company_id,
                'salary_group_ids' => [(int) $req->salary_group_id],
                'year'             => $req->year,
                'month'            => $req->month,
                'get_list'         => 1,
            ])->with('status', "🗑️ Deleted {$cnt} payslip(s) for {$g->salary_group_name}. Attendance, manual deductions, and overtime preserved — ready to regenerate.");
    }

    public function generateRun(Request $req)
    {
        // Back-compat: accept old singular salary_group_id
        if (!$req->has('salary_group_ids') && $req->filled('salary_group_id')) {
            $req->merge(['salary_group_ids' => [$req->input('salary_group_id')]]);
        }

        $req->validate([
            'company_id'         => 'required|integer',
            'salary_group_ids'   => 'required|array|min:1',
            'salary_group_ids.*' => 'integer',
            'year'               => 'required|integer|between:2020,2030',
            'month'              => 'required|integer|between:1,12',
            'emp_ids'            => 'nullable|array',
            'emp_ids.*'          => 'integer',
        ]);

        $companyId = $req->integer('company_id');
        $year      = $req->integer('year');
        $month     = $req->integer('month');
        $groupIds  = array_values(array_unique(array_map('intval', $req->input('salary_group_ids', []))));

        // Find or create the run (one run per company × period)
        $run = SalaryRun::firstOrCreate(
            ['company_id' => $companyId, 'period_year' => $year, 'period_month' => $month],
            [
                'run_code'       => sprintf('SALRUN-%04d-%02d', $year, $month),
                'status'         => 'Draft',
                'run_started_at' => now(),
                'created_by'     => auth()->user()?->name ?? 'system',
            ]
        );

        if ($run->status === 'Posted') {
            return back()->with('status', 'Cannot regenerate — run is already Posted.');
        }

        // Resolve which employees to compute for
        $empIds = $req->input('emp_ids', []);
        if (empty($empIds)) {
            // No checkboxes? Fall back to ALL employees in the selected groups
            $empIds = \App\Models\Employee::where('active_flag', true)
                ->where('company_id', $companyId)
                ->whereIn('salary_group_id', $groupIds)
                ->pluck('emp_id')->all();
        }

        if (empty($empIds)) {
            return back()->with('status', 'No employees in the selected company × salary group(s).');
        }

        // Wipe payslips for these employees in this run, then recompute fresh
        Payslip::where('run_id', $run->run_id)->whereIn('emp_id', $empIds)->delete();

        /** @var PayrollEngine $engine */
        $engine = app(PayrollEngine::class);

        // Loop per-group for per-group messaging (single run row is shared)
        $employees  = \App\Models\Employee::with('salary_group')->whereIn('emp_id', $empIds)->get();
        $byGroup    = $employees->groupBy('salary_group_id');
        $groupNames = \App\Models\SalaryGroup::whereIn('salary_group_id', $groupIds)
            ->pluck('salary_group_name', 'salary_group_id');

        $generated = 0;
        $skipped   = [];
        $messages  = [];
        foreach ($groupIds as $gid) {
            $gen = 0; $sk = [];
            foreach ($byGroup->get($gid, collect()) as $emp) {
                if (!$emp->current_gross && !$emp->current_basic) {
                    $sk[] = "{$emp->emp_id} (no salary data)";
                    continue;
                }
                try {
                    $engine->computeForEmployee($emp, $run);
                    $gen++;
                } catch (\Throwable $ex) {
                    $sk[] = "{$emp->emp_id} (" . substr($ex->getMessage(), 0, 50) . ")";
                }
            }
            $generated += $gen;
            $skipped    = array_merge($skipped, $sk);
            $line = ($groupNames[$gid] ?? "Group {$gid}") . ": {$gen} generated";
            if ($sk) $line .= " · skipped " . count($sk);
            $messages[] = $line;
        }

        // Refresh the run's totals from current payslips
        $totals = Payslip::where('run_id', $run->run_id)->selectRaw('
            COUNT(*) as cnt,
            SUM(gross_earnings) as earnings,
            SUM(total_deductions) as deductions,
            SUM(net_pay) as net,
            SUM(total_employer_cost) as ctc,
            SUM(epf_emp) as pf_emp, SUM(employer_pf) as pf_er,
            SUM(eps) as eps, SUM(edli) as edli, SUM(pf_admin) as admin,
            SUM(esi_emp) as esi_emp, SUM(employer_esi) as esi_er,
            SUM(pt) as pt, SUM(lwf_emp) as lwf_emp, SUM(lwf_employer) as lwf_er,
            SUM(tds) as tds, SUM(bonus) as bonus, SUM(gratuity_provision) as grat
        ')->first();

        $run->update([
            'eligible_emp_count'       => $totals->cnt ?? 0,
            'total_earnings'           => $totals->earnings ?? 0,
            'total_deductions'         => $totals->deductions ?? 0,
            'total_net_payout'         => $totals->net ?? 0,
            'total_employer_cost'      => $totals->ctc ?? 0,
            'total_pf_emp'             => $totals->pf_emp ?? 0,
            'total_pf_er'              => $totals->pf_er ?? 0,
            'total_eps'                => $totals->eps ?? 0,
            'total_edli'               => $totals->edli ?? 0,
            'total_admin'              => $totals->admin ?? 0,
            'total_esi_emp'            => $totals->esi_emp ?? 0,
            'total_esi_er'             => $totals->esi_er ?? 0,
            'total_pt'                 => $totals->pt ?? 0,
            'total_lwf_emp'            => $totals->lwf_emp ?? 0,
            'total_lwf_er'             => $totals->lwf_er ?? 0,
            'total_tds'                => $totals->tds ?? 0,
            'total_bonus_provision'    => $totals->bonus ?? 0,
            'total_gratuity_provision' => $totals->grat ?? 0,
            'calc_completed_at'        => now(),
            'status'                   => $run->status === 'Posted' ? 'Posted' : 'Computed',
        ]);

        $msg = "Generated {$generated} payslips — " . implode(' | ', $messages);
        if (!empty($skipped)) {
            $msg .= ". Skipped " . count($skipped) . ": " . implode(', ', array_slice($skipped, 0, 3))
                  . (count($skipped) > 3 ? ', …' : '');
        }
        return redirect()->route('payroll.generate', [
                'company_id'       => $companyId,
                'salary_group_ids' => $groupIds,
                'year'             => $year,
                'month'            => $month,
                'get_list'         => 1,
            ])->with('status', $msg);
    }

    /**
     * Generate payroll for EVERY salary group of the picked company × period
     * in one click. Loops every active group, fires the engine on each
     * employee in that group, and returns a combined summary so the user
     * doesn't need to pick a group manually.
     *
     * If `company_id` is omitted, uses session('active_company_id').
     */
    public function generateAllGroups(Request $req)
    {
        $req->validate([
            'company_id' => 'nullable|integer',
            'year'       => 'required|integer|between:2020,2030',
            'month'      => 'required|integer|between:1,12',
        ]);

        $companyId = (int) ($req->input('company_id') ?: session('active_company_id', 0));
        $year      = $req->integer('year');
        $month     = $req->integer('month');

        if (!$companyId) {
            return back()->with('status', '❌ No company selected. Pick a company first.');
        }

        // Every salary group tied to the company that has at least one
        // active employee — skip empty groups silently.
        $activeGroupIds = \App\Models\Employee::where('company_id', $companyId)
            ->where('active_flag', true)
            ->whereNotNull('salary_group_id')
            ->distinct()
            ->pluck('salary_group_id');

        $groups = $this->salaryGroupsForCompany($companyId)
            ->whereIn('salary_group_id', $activeGroupIds)
            ->values();

        if ($groups->isEmpty()) {
            return back()->with('status', "❌ No salary groups with active employees in company {$companyId}.");
        }

        $run = SalaryRun::firstOrCreate(
            ['company_id' => $companyId, 'period_year' => $year, 'period_month' => $month],
            [
                'run_code'       => sprintf('SALRUN-%04d-%02d', $year, $month),
                'status'         => 'Draft',
                'run_started_at' => now(),
                'created_by'     => auth()->user()?->name ?? 'system',
            ]
        );

        if ($run->status === 'Posted') {
            return back()->with('status', 'Cannot regenerate — run is already Posted for this period.');
        }

        /** @var PayrollEngine $engine */
        $engine = app(PayrollEngine::class);

        $totalGen   = 0;
        $totalSkip  = 0;
        $perGroup   = [];   // [group_name => generated_count]
        $allSkipped = [];

        foreach ($groups as $g) {
            $empIds = \App\Models\Employee::where('active_flag', true)
                ->where('company_id', $companyId)
                ->where('salary_group_id', $g->salary_group_id)
                ->pluck('emp_id');

            if ($empIds->isEmpty()) continue;

            // Wipe these employees' payslips in this run first (idempotent re-run)
            Payslip::where('run_id', $run->run_id)
                ->whereIn('emp_id', $empIds)
                ->delete();

            $gen = 0;
            $employees = \App\Models\Employee::whereIn('emp_id', $empIds)->get();
            foreach ($employees as $emp) {
                if (!$emp->current_gross && !$emp->current_basic) {
                    $allSkipped[] = "{$emp->emp_id} (no salary data)";
                    $totalSkip++;
                    continue;
                }
                try {
                    $engine->computeForEmployee($emp, $run);
                    $gen++;
                } catch (\Throwable $ex) {
                    $allSkipped[] = "{$emp->emp_id} (" . substr($ex->getMessage(), 0, 50) . ')';
                    $totalSkip++;
                }
            }

            $totalGen          += $gen;
            $perGroup[$g->salary_group_name] = $gen;
        }

        // Refresh totals on the run
        $totals = Payslip::where('run_id', $run->run_id)->selectRaw('
            COUNT(*) as cnt,
            SUM(gross_earnings) as earnings,
            SUM(total_deductions) as deductions,
            SUM(net_pay) as net,
            SUM(total_employer_cost) as ctc,
            SUM(epf_emp) as pf_emp, SUM(employer_pf) as pf_er,
            SUM(eps) as eps, SUM(edli) as edli, SUM(pf_admin) as admin,
            SUM(esi_emp) as esi_emp, SUM(employer_esi) as esi_er,
            SUM(pt) as pt, SUM(lwf_emp) as lwf_emp, SUM(lwf_employer) as lwf_er,
            SUM(tds) as tds, SUM(bonus) as bonus, SUM(gratuity_provision) as grat
        ')->first();

        $run->update([
            'eligible_emp_count'       => $totals->cnt ?? 0,
            'total_earnings'           => $totals->earnings ?? 0,
            'total_deductions'         => $totals->deductions ?? 0,
            'total_net_payout'         => $totals->net ?? 0,
            'total_employer_cost'      => $totals->ctc ?? 0,
            'total_pf_emp'             => $totals->pf_emp ?? 0,
            'total_pf_er'              => $totals->pf_er ?? 0,
            'total_eps'                => $totals->eps ?? 0,
            'total_edli'               => $totals->edli ?? 0,
            'total_admin'              => $totals->admin ?? 0,
            'total_esi_emp'            => $totals->esi_emp ?? 0,
            'total_esi_er'             => $totals->esi_er ?? 0,
            'total_pt'                 => $totals->pt ?? 0,
            'total_lwf_emp'            => $totals->lwf_emp ?? 0,
            'total_lwf_er'             => $totals->lwf_er ?? 0,
            'total_tds'                => $totals->tds ?? 0,
            'total_bonus_provision'    => $totals->bonus ?? 0,
            'total_gratuity_provision' => $totals->grat ?? 0,
            'calc_completed_at'        => now(),
            'status'                   => $run->status === 'Posted' ? 'Posted' : 'Computed',
        ]);

        $perGroupSummary = collect($perGroup)
            ->map(fn ($n, $name) => "{$name}: {$n}")
            ->implode(' • ');

        $msg = "✅ Generated {$totalGen} payslips across "
             . $groups->count() . " group(s) for "
             . \DateTime::createFromFormat('!m', $month)->format('F') . " {$year}. "
             . "[ {$perGroupSummary} ]";

        if ($totalSkip > 0) {
            $msg .= " Skipped {$totalSkip}: " . implode(', ', array_slice($allSkipped, 0, 3))
                  . (count($allSkipped) > 3 ? ', …' : '');
        }

        return redirect()->route('payroll.generate', [
                'company_id' => $companyId,
                'year'       => $year,
                'month'      => $month,
            ])->with('status', $msg);
    }

    public function create()
    {
        $companies = Company::orderBy('company_name')->get();
        return view('payroll.run-create', compact('companies'));
    }

    public function store(Request $req)
    {
        $req->validate([
            'company_id'   => 'required|integer',
            'period_year'  => 'required|integer|between:2020,2030',
            'period_month' => 'required|integer|between:1,12',
        ]);

        $existing = SalaryRun::where('company_id', $req->integer('company_id'))
            ->where('period_year', $req->integer('period_year'))
            ->where('period_month', $req->integer('period_month'))
            ->first();
        if ($existing) {
            return redirect()->route('payroll.runs.show', $existing->run_id)
                ->with('status', 'A salary run already exists for this period.');
        }

        $run = SalaryRun::create([
            'run_code'       => sprintf('SALRUN-%04d-%02d', $req->integer('period_year'), $req->integer('period_month')),
            'period_year'    => $req->integer('period_year'),
            'period_month'   => $req->integer('period_month'),
            'company_id'     => $req->integer('company_id'),
            'status'         => 'Draft',
            'run_started_at' => now(),
            'created_by'     => auth()->user()?->name ?? 'system',
        ]);

        return redirect()->route('payroll.runs.show', $run->run_id)
            ->with('status', 'Salary run created in Draft. Click "Run Payroll Engine" to compute payslips.');
    }

    public function show($runId)
    {
        $run = SalaryRun::findOrFail($runId);
        $payslipCount = Payslip::where('run_id', $runId)->count();
        $totals = Payslip::where('run_id', $runId)
            ->selectRaw('SUM(gross_earnings) as gross, SUM(net_pay) as net, COUNT(*) as cnt')
            ->first();
        return view('payroll.run-show', compact('run', 'payslipCount', 'totals'));
    }

    /**
     * Trigger the PayrollEngine to compute payslips for every active employee
     * in this run's company, for this run's period. Idempotent — wipes and
     * regenerates payslips for the run.
     */
    public function compute($runId)
    {
        $run = SalaryRun::findOrFail($runId);

        if ($run->status === 'Posted') {
            return back()->with('status', 'Cannot recompute a posted run.');
        }

        // Wipe any prior payslips for this run so we recompute fresh
        Payslip::where('run_id', $run->run_id)->delete();

        try {
            /** @var PayrollEngine $engine */
            $engine = app(PayrollEngine::class);

            // Engine creates a new run internally — to keep this run, we delete
            // it first then call run() and copy the new run's id back, OR we
            // call computeForEmployee directly per employee. The latter is safer.
            $employees = \App\Models\Employee::where('company_id', $run->company_id)
                ->where('active_flag', true)
                ->get();

            $totals = [
                'earnings' => 0, 'deductions' => 0, 'net' => 0, 'ctc' => 0,
                'pf_emp' => 0, 'pf_er' => 0, 'eps' => 0, 'edli' => 0, 'admin' => 0,
                'esi_emp' => 0, 'esi_er' => 0, 'pt' => 0, 'lwf_emp' => 0, 'lwf_er' => 0,
                'tds' => 0, 'bonus' => 0, 'gratuity' => 0,
            ];

            $skipped = [];
            foreach ($employees as $emp) {
                // Defensive: skip rows that would crash the engine (no gross at all)
                if (!$emp->current_gross && !$emp->current_basic) {
                    $skipped[] = $emp->emp_id . ' (no salary data)';
                    continue;
                }
                try {
                    $p = $engine->computeForEmployee($emp, $run);
                } catch (\Throwable $ex) {
                    $skipped[] = $emp->emp_id . ' (' . substr($ex->getMessage(), 0, 60) . ')';
                    continue;
                }
                $totals['earnings']    += $p->gross_earnings;
                $totals['deductions']  += $p->total_deductions;
                $totals['net']         += $p->net_pay;
                $totals['ctc']         += $p->total_employer_cost;
                $totals['pf_emp']      += $p->epf_emp;
                $totals['pf_er']       += $p->employer_pf;
                $totals['eps']         += $p->eps;
                $totals['edli']        += $p->edli;
                $totals['admin']       += $p->pf_admin;
                $totals['esi_emp']     += $p->esi_emp;
                $totals['esi_er']      += $p->employer_esi;
                $totals['pt']          += $p->pt;
                $totals['lwf_emp']     += $p->lwf_emp;
                $totals['lwf_er']      += $p->lwf_employer;
                $totals['tds']         += $p->tds;
                $totals['bonus']       += $p->bonus;
                $totals['gratuity']    += $p->gratuity_provision;
            }

            $run->update([
                'eligible_emp_count'       => $employees->count(),
                'total_earnings'           => $totals['earnings'],
                'total_deductions'         => $totals['deductions'],
                'total_net_payout'         => $totals['net'],
                'total_employer_cost'      => $totals['ctc'],
                'total_pf_emp'             => $totals['pf_emp'],
                'total_pf_er'              => $totals['pf_er'],
                'total_eps'                => $totals['eps'],
                'total_edli'               => $totals['edli'],
                'total_admin'              => $totals['admin'],
                'total_esi_emp'            => $totals['esi_emp'],
                'total_esi_er'             => $totals['esi_er'],
                'total_pt'                 => $totals['pt'],
                'total_lwf_emp'            => $totals['lwf_emp'],
                'total_lwf_er'             => $totals['lwf_er'],
                'total_tds'                => $totals['tds'],
                'total_bonus_provision'    => $totals['bonus'],
                'total_gratuity_provision' => $totals['gratuity'],
                'calc_completed_at'        => now(),
                'status'                   => 'Computed',
            ]);

            $msg = "Payroll computed: " . ($employees->count() - count($skipped)) . " of {$employees->count()} payslips generated.";
            if (count($skipped) > 0) {
                $msg .= " Skipped " . count($skipped) . ": " . implode(', ', array_slice($skipped, 0, 5));
                if (count($skipped) > 5) $msg .= ", &hellip;";
            }
            return redirect()->route('payroll.runs.show', $run->run_id)->with('status', $msg);
        } catch (\Throwable $e) {
            return back()->with('status', 'Engine error: ' . $e->getMessage());
        }
    }

    public function approve($runId)
    {
        $run = SalaryRun::findOrFail($runId);
        $run->update(['status' => 'Approved', 'finance_approved_at' => now()]);
        return back()->with('status', 'Run approved.');
    }

    public function post($runId)
    {
        $run = SalaryRun::findOrFail($runId);
        $run->update(['status' => 'Posted', 'posted_at' => now()]);
        return back()->with('status', 'Run posted to GL.');
    }

    /**
     * Generate the salary disbursement bank file (CSV — universally accepted
     * by HDFC / ICICI / SBI / Axis bulk-NEFT portals).
     *
     * Returns a streamed CSV download named SALRUN-{YEAR}-{MONTH}-bank.csv.
     */
    public function bankFile($runId)
    {
        $run = SalaryRun::findOrFail($runId);

        $payslips = Payslip::where('run_id', $run->run_id)
            ->where(function ($q) { $q->where('net_pay', '>', 0); })
            ->get();

        if ($payslips->isEmpty()) {
            return back()->with('status', 'No payslips with positive net pay. Run payroll first.');
        }

        $filename = "{$run->run_code}-bank.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ];

        $callback = function () use ($payslips, $run) {
            $out = fopen('php://output', 'w');
            // BOM for Excel compatibility
            fprintf($out, "\xEF\xBB\xBF");

            // Header row — HDFC / ICICI bulk NEFT format
            fputcsv($out, [
                'Beneficiary Code',
                'Beneficiary Name',
                'Beneficiary Account No',
                'IFSC Code',
                'Amount',
                'Currency',
                'Payment Type',
                'Customer Reference',
                'Narration',
                'Beneficiary Email',
            ]);

            foreach ($payslips as $p) {
                $emp = \App\Models\Employee::where('emp_id', $p->emp_id)->first();
                if (!$emp) continue;

                fputcsv($out, [
                    $emp->emp_id,
                    $emp->account_holder_name ?: $emp->full_name,
                    $emp->bank_account_no ?: '',
                    $emp->bank_ifsc ?: '',
                    number_format($p->net_pay, 2, '.', ''),
                    'INR',
                    $emp->salary_disbursement_mode ?: 'NEFT',
                    sprintf('SAL-%04d-%02d-%d', $run->period_year, $run->period_month, $emp->emp_id),
                    sprintf('Salary %s %d', \DateTime::createFromFormat('!m', $run->period_month)->format('M'), $run->period_year),
                    $emp->personal_email ?: $emp->company_email ?: '',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
