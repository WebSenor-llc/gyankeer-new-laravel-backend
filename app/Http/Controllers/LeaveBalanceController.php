<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Models\Employee;
use App\Models\SalaryGroup;
use Illuminate\Http\Request;

/**
 * Per-employee leave-balance view + import endpoint.
 *
 * Reads leave_balances rows (which are updated automatically by the payroll
 * engine after each compute) and renders them in a sortable table grouped by
 * salary group. Supports CSV / Excel export.
 */
class LeaveBalanceController extends Controller
{
    public function index(Request $req)
    {
        $cid           = (int) session('active_company_id', 0);
        $salaryGroupId = (int) $req->input('salary_group_id', 0);
        $fy            = (int) $req->input('fy', 2027);    // FY-end year — 2027 = Apr-2026 .. Mar-2027
        $format        = strtolower($req->input('format', ''));

        // Pull EVERY leave_balance row for the FY. We deliberately don't
        // filter by company here — the CSV import may have created rows
        // with NULL company_id for unknown employees, and the page is
        // meant to surface all of them so the user can see what was loaded.
        // The group filter (when picked) is the only narrowing applied.
        $balancesQ = LeaveBalance::where('fy', $fy)
            ->when($salaryGroupId, fn ($q) => $q->whereIn(
                'emp_id',
                Employee::where('salary_group_id', $salaryGroupId)->pluck('emp_id')
            ));

        // Diagnostic: total rows in this FY across ALL groups/companies
        $totalForFy = LeaveBalance::where('fy', $fy)->count();

        $balances = $balancesQ->get();

        // Pivot: emp_id => ['CL' => row, 'PL' => row, ...]
        $byEmp = $balances->groupBy('emp_id')->map(fn ($rows) => $rows->keyBy('leave_code'));

        // Resolve the matching employee rows for display metadata, but DO NOT
        // drop balances that don't have an employee record — show them with
        // the snapshot name from leave_balances itself.
        $emps = Employee::with(['salary_group','company'])
            ->whereIn('emp_id', $byEmp->keys())
            ->get()
            ->keyBy('emp_id');

        // Build flat rows for table + export. Iterate over the BALANCE keys
        // (not employees) so orphan-employee balances are still shown.
        $rows = collect();
        foreach ($byEmp as $empId => $codeMap) {
            $e  = $emps->get($empId);
            $cl = $codeMap->get('CL');
            $pl = $codeMap->get('PL');
            $sl = $codeMap->get('SL');
            $sample = $cl ?: $pl ?: $sl;        // any row for name fallback
            $rows->push([
                'emp_id'       => $empId,
                'name'         => $e?->full_name ?? $sample?->employee_name ?? '(unknown)',
                'group'        => $e?->salary_group?->salary_group_name ?? '—',
                'sgid'         => $e?->salary_group_id ?? 0,
                'cl_opening'   => (float) ($cl->opening_balance ?? 0),
                'cl_availed'   => (float) ($cl->availed_ytd ?? 0),
                'cl_closing'   => (float) ($cl->closing_balance ?? 0),
                'pl_opening'   => (float) ($pl->opening_balance ?? 0),
                'pl_availed'   => (float) ($pl->availed_ytd ?? 0),
                'pl_closing'   => (float) ($pl->closing_balance ?? 0),
                'sl_opening'   => (float) ($sl->opening_balance ?? 0),
                'sl_availed'   => (float) ($sl->availed_ytd ?? 0),
                'sl_closing'   => (float) ($sl->closing_balance ?? 0),
            ]);
        }
        // Sort by group then emp_id for stable display.
        // (sortBy with raw closure — multi-key array form has bitten us before.)
        $rows = $rows->sortBy(fn ($r) => sprintf('%010d-%010d', $r['sgid'], $r['emp_id']))->values();

        // CSV / Excel export
        if (in_array($format, ['csv', 'xls'], true)) {
            return $this->export($format, $rows, $fy);
        }

        $salaryGroups = SalaryGroup::query()
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->orderBy('salary_group_name')->get();

        return view('leaves.balances', compact(
            'rows', 'salaryGroups', 'salaryGroupId', 'fy', 'totalForFy'
        ));
    }

    /**
     * CSV / Excel — uses the same HTML-as-XLS trick from StatutoryController.
     */
    protected function export(string $format, $rows, int $fy)
    {
        $headers = [
            'Emp ID', 'Name', 'Salary Group',
            'CL Opening', 'CL Availed', 'CL Closing',
            'PL Opening', 'PL Availed', 'PL Closing',
            'SL Opening', 'SL Availed', 'SL Closing',
        ];

        $data = $rows->map(fn ($r) => [
            $r['emp_id'], $r['name'], $r['group'],
            $r['cl_opening'], $r['cl_availed'], $r['cl_closing'],
            $r['pl_opening'], $r['pl_availed'], $r['pl_closing'],
            $r['sl_opening'], $r['sl_availed'], $r['sl_closing'],
        ])->all();

        $fyLabel = ($fy - 1) . '-' . substr((string)$fy, -2);
        $stem    = "leave-balances-{$fyLabel}";

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $data) {
                $h = fopen('php://output', 'w');
                fwrite($h, "\xEF\xBB\xBF");
                fputcsv($h, $headers);
                foreach ($data as $r) fputcsv($h, $r);
                fclose($h);
            }, "{$stem}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        // XLS — same HTML-as-Excel pattern as StatutoryController
        $html  = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel'>";
        $html .= "<head><meta charset='UTF-8'><title>Leave Balances {$fyLabel}</title>";
        $html .= "<style>td,th{border:1px solid #888;padding:4px 6px;font-size:11px} th{background:#E5E7EB;font-weight:bold} td.num{text-align:right}</style></head><body>";
        $html .= "<h3>Leave Balances — FY {$fyLabel}</h3><table><thead><tr>";
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

    /**
     * Run the LeaveBalanceMar2026Seeder on demand from the UI. Wipes any
     * existing FY 2027 rows first so re-imports are clean.
     */
    public function import(Request $req)
    {
        \Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\LeaveBalanceMar2026Seeder',
            '--force' => true,
        ]);
        $out = trim(\Artisan::output());
        return back()->with('status', "✅ {$out}");
    }
}
