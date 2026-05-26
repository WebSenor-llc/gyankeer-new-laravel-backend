<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDaily;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class AttendanceLeaveController extends Controller
{
    /**
     * Map an emp_type filter value to all aliases stored in the DB.
     * Different seeders/imports historically stored employee_type as either
     * the short code ('WK', 'SB', 'ST') or the full label ('Worker',
     * 'Sub-Staff', 'Staff'). This helper makes filters tolerant of both.
     */
    protected function expandEmpType(string $val): array
    {
        return match (strtolower(trim($val))) {
            'wk', 'worker', 'w', 'l', 'labour', 'labor'      => ['WK', 'wk', 'Worker', 'worker', 'W', 'L', 'Labour', 'labour'],
            'sb', 'sub-staff', 'substaff', 'sub_staff', 'ss' => ['SB', 'sb', 'Sub-Staff', 'sub-staff', 'SubStaff', 'Sub Staff'],
            'st', 'staff'                                    => ['ST', 'st', 'Staff', 'staff'],
            default                                          => [$val],
        };
    }

    public function daily(Request $req)
    {
        $date = $req->input('date', today()->toDateString());
        $records = AttendanceDaily::where('attn_date', $date)->paginate(50)->appends($req->query());
        $totals = [
            'present' => AttendanceDaily::where('attn_date', $date)->where('status', 'Present')->count(),
            'absent'  => AttendanceDaily::where('attn_date', $date)->where('status', 'Absent')->count(),
            'leave'   => AttendanceDaily::where('attn_date', $date)->where('status', 'On Leave')->count(),
            'duty'    => AttendanceDaily::where('attn_date', $date)->where('status', 'On Duty')->count(),
            'total'   => AttendanceDaily::where('attn_date', $date)->count(),
        ];
        return view('attendance.daily', compact('records', 'date', 'totals'));
    }

    public function manual()
    {
        $employees = Employee::where('active_flag', true)->take(500)->get();
        return view('attendance.manual', compact('employees'));
    }

    public function bulkMark(Request $req)
    {
        $req->validate([
            'date'   => 'required|date',
            'status' => 'array',
        ]);

        $date    = $req->input('date');
        $statuses = $req->input('status', []);
        $reasons  = $req->input('reason',  []);
        $saved    = 0;

        foreach ($statuses as $empId => $status) {
            if (!$status) continue;  // "— Skip —"

            $emp = Employee::where('emp_id', $empId)->first();
            if (!$emp) continue;

            AttendanceDaily::updateOrCreate(
                ['emp_id' => $empId, 'attn_date' => $date],
                [
                    'company_id'    => $emp->company_id,
                    'employee_name' => $emp->full_name,
                    'shift_id'      => $emp->shift_id,
                    'shift_name'    => $emp->shift->shift_name ?? null,
                    'status'        => $status,
                    'status_reason' => $reasons[$empId] ?? null,
                    'source'        => 'manual',
                    'approval_status' => 'Approved',
                ]
            );
            $saved++;
        }

        return redirect()->route('attendance.daily', ['date' => $date])
            ->with('status', "Saved attendance for {$saved} employees on {$date}.");
    }

    public function uploadForm()
    {
        return view('attendance.upload');
    }

    public function upload(Request $req)
    {
        return back()->with('status', 'Upload not implemented.');
    }

    public function downloadTemplate()
    {
        return back()->with('status', 'Template download requires the maatwebsite/excel package.');
    }

    public function setReporting(Request $req)
    {
        $cid = (int) session('active_company_id', 0);
        $q   = Employee::with('department')->where('active_flag', true);
        if ($cid) $q->where('company_id', $cid);

        if ($req->filled('search_q')) {
            $sq = $req->search_q;
            $q->where(function ($w) use ($sq) {
                $w->where('full_name', 'like', "%$sq%")
                  ->orWhere('emp_id', 'like', "%$sq%");
            });
        }

        // Paginate to 50 per page — otherwise rendering 472×472 select options
        // blows past PHP's 128 MB memory limit.
        $employees = $q->orderBy('full_name')->paginate(50)->appends($req->query());

        // Lean manager list (just id + name) — used by JavaScript datalist.
        $managers = Employee::where('active_flag', true)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->orderBy('full_name')
            ->get(['emp_id', 'full_name']);

        return view('attendance.set-reporting', compact('employees', 'managers'));
    }

    public function setReportingSave(Request $req)
    {
        $assignments = $req->input('manager', []);
        $updated = 0;
        foreach ($assignments as $empId => $managerId) {
            $managerId = $managerId ?: null;
            $emp = Employee::where('emp_id', $empId)->first();
            if ($emp && (string) $emp->reports_to_emp_id !== (string) $managerId) {
                $emp->update(['reports_to_emp_id' => $managerId]);
                $updated++;
            }
        }
        return back()->with('status', "Updated reporting manager for {$updated} employees.");
    }

    /**
     * Monthly attendance grid — employees × days matrix with editable status cells.
     */
    public function bulkGrid(Request $req)
    {
        $year  = (int) $req->input('year',  now()->year);
        $month = (int) $req->input('month', now()->month);
        $cid   = (int) session('active_company_id', 0);

        $totalDays = (int) \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $empQ = Employee::with('department')->where('active_flag', true);
        if ($cid) $empQ->where('company_id', $cid);
        if ($req->filled('q')) {
            $q = $req->q;
            $empQ->where(function ($w) use ($q) {
                $w->where('full_name', 'like', "%$q%")
                  ->orWhere('emp_id', 'like', "%$q%");
            });
        }
        if ($req->filled('dept_id')) $empQ->where('dept_id', $req->dept_id);
        $employees = $empQ->orderBy('emp_id')->paginate(100)->appends($req->query());

        // Pre-load existing attendance for these employees in this period
        $empIds = $employees->pluck('emp_id')->all();
        $start  = \Carbon\Carbon::createFromDate($year, $month, 1)->toDateString();
        $end    = \Carbon\Carbon::createFromDate($year, $month, $totalDays)->toDateString();
        $existing = AttendanceDaily::whereIn('emp_id', $empIds)
            ->whereBetween('attn_date', [$start, $end])
            ->get()
            ->groupBy('emp_id')
            ->map(function ($rows) {
                return $rows->keyBy(fn ($r) => \Carbon\Carbon::parse($r->attn_date)->format('j'));
            });

        $departments = \App\Models\Department::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('dept_name')->get();

        // Build day-of-week labels (S/M/T/W/T/F/S) for header
        $dayLabels = [];
        for ($d = 1; $d <= $totalDays; $d++) {
            $dt = \Carbon\Carbon::createFromDate($year, $month, $d);
            $dayLabels[$d] = [
                'date'  => $dt->toDateString(),
                'dow'   => $dt->format('D'),    // Mon, Tue, etc.
                'short' => substr($dt->format('D'), 0, 1),  // M, T, W, etc.
                'is_sun'=> $dt->dayOfWeek === 0,
            ];
        }

        return view('attendance.grid', compact(
            'employees', 'existing', 'year', 'month', 'totalDays',
            'dayLabels', 'departments'
        ));
    }

    public function bulkGridSave(Request $req)
    {
        $year  = (int) $req->input('year');
        $month = (int) $req->input('month');
        $cells = $req->input('cell', []);  // cell[empId][day] = status code
        $saved = 0;

        foreach ($cells as $empId => $days) {
            $emp = Employee::where('emp_id', $empId)->first();
            if (!$emp) continue;
            foreach ($days as $day => $status) {
                if ($status === '' || $status === null) continue;
                $date = \Carbon\Carbon::createFromDate($year, $month, (int) $day)->toDateString();
                AttendanceDaily::updateOrCreate(
                    ['emp_id' => $empId, 'attn_date' => $date],
                    [
                        'company_id'      => $emp->company_id,
                        'employee_name'   => $emp->full_name,
                        'shift_id'        => $emp->shift_id,
                        'shift_name'      => $emp->shift->shift_name ?? null,
                        'status'          => $this->expandStatus($status),
                        'source'          => 'grid',
                        'approval_status' => 'Approved',
                    ]
                );
                $saved++;
            }
        }

        return redirect()
            ->route('attendance.grid', ['year' => $year, 'month' => $month])
            ->with('status', "Saved {$saved} attendance entries for " . \DateTime::createFromFormat('!m', $month)->format('F') . " {$year}.");
    }

    /** Convert single-letter codes to full status labels. */
    private function expandStatus(string $code): string
    {
        return match (strtoupper($code)) {
            'P' => 'Present',
            'A' => 'Absent',
            'L' => 'On Leave',
            'D' => 'On Duty',
            'H' => 'Half Day',
            'W' => 'Weekly Off',
            'F' => 'Holiday',
            default => $code,
        };
    }

    /**
     * SUMMARY ENTRY mode — for each employee, enter (Present count, W/Off count,
     * Leave from-to, Absent from-to). The system distributes intelligently
     * across the month and writes one row per (emp × day).
     *
     * Typical input pattern: 25 Present + 4 Sundays as W/Off + 1 leave on a date.
     */
    public function summaryEntry(Request $req)
    {
        $year  = (int) $req->input('year',  now()->year);
        $month = (int) $req->input('month', now()->month);
        $cid   = (int) session('active_company_id', 0);

        $totalDays = (int) \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

        // Count Sundays in the month — for the W/Off auto-default
        $sundays = 0;
        for ($d = 1; $d <= $totalDays; $d++) {
            if (\Carbon\Carbon::createFromDate($year, $month, $d)->dayOfWeek === 0) $sundays++;
        }

        $empQ = Employee::with('department')->where('active_flag', true);
        if ($cid) $empQ->where('company_id', $cid);
        if ($req->filled('q')) {
            $q = $req->q;
            $empQ->where(function ($w) use ($q) {
                $w->where('full_name', 'like', "%$q%")->orWhere('emp_id', 'like', "%$q%");
            });
        }
        if ($req->filled('dept_id')) $empQ->where('dept_id', $req->dept_id);
        $employees = $empQ->orderBy('emp_id')->paginate(100)->appends($req->query());

        // Pre-compute existing summary per employee for the period (helpful for review)
        $empIds = $employees->pluck('emp_id')->all();
        $start  = \Carbon\Carbon::createFromDate($year, $month, 1)->toDateString();
        $end    = \Carbon\Carbon::createFromDate($year, $month, $totalDays)->toDateString();
        $existing = AttendanceDaily::whereIn('emp_id', $empIds)
            ->whereBetween('attn_date', [$start, $end])
            ->get()
            ->groupBy('emp_id')
            ->map(function ($rows) {
                $counts = ['Present'=>0,'Absent'=>0,'On Leave'=>0,'On Duty'=>0,'Half Day'=>0,'Weekly Off'=>0,'Holiday'=>0];
                foreach ($rows as $r) {
                    if (isset($counts[$r->status])) $counts[$r->status]++;
                }
                return $counts;
            });

        $departments = \App\Models\Department::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('dept_name')->get();

        return view('attendance.summary', compact(
            'employees', 'existing', 'year', 'month', 'totalDays', 'sundays', 'departments'
        ));
    }

    public function summaryEntrySave(Request $req)
    {
        $year  = (int) $req->input('year');
        $month = (int) $req->input('month');
        $rows  = $req->input('row', []);   // row[empId] = ['p'=>25,'w'=>4,'l_from'=>'2026-04-15','l_to'=>'2026-04-15','a_from'=>'','a_to'=>'']

        $totalDays = (int) \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

        // Build day-of-week map once
        $dows = [];
        for ($d = 1; $d <= $totalDays; $d++) {
            $dows[$d] = \Carbon\Carbon::createFromDate($year, $month, $d);
        }

        $employeesProcessed = 0;
        $totalCells = 0;

        foreach ($rows as $empId => $cfg) {
            $emp = Employee::where('emp_id', $empId)->first();
            if (!$emp) continue;

            // Skip rows the user didn't touch — otherwise we'd overwrite every day with "Present".
            $pCount = (int) ($cfg['p'] ?? 0);
            $wCount = (int) ($cfg['w'] ?? 0);
            $hasLeaveDates  = !empty($cfg['l_from']) || !empty($cfg['l_to']);
            $hasAbsentDates = !empty($cfg['a_from']) || !empty($cfg['a_to']);
            if ($pCount === 0 && $wCount === 0 && !$hasLeaveDates && !$hasAbsentDates) {
                continue;
            }

            // Default everything to empty
            $statusByDay = array_fill_keys(range(1, $totalDays), null);

            // Step 1 — Mark Sundays as Weekly Off (up to W count)
            $woCount = (int) ($cfg['w'] ?? 0);
            $sundays = collect($dows)->filter(fn($dt) => $dt->dayOfWeek === 0)->keys()->all();
            $woAssigned = 0;
            foreach ($sundays as $day) {
                if ($woAssigned >= $woCount) break;
                $statusByDay[$day] = 'Weekly Off';
                $woAssigned++;
            }
            // If still W left over (e.g. user wants 5 W but month has only 4 Sundays),
            // assign extras to Saturdays
            if ($woAssigned < $woCount) {
                $saturdays = collect($dows)->filter(fn($dt) => $dt->dayOfWeek === 6)->keys()->all();
                foreach ($saturdays as $day) {
                    if ($woAssigned >= $woCount) break;
                    if ($statusByDay[$day] === null) {
                        $statusByDay[$day] = 'Weekly Off';
                        $woAssigned++;
                    }
                }
            }

            // Step 2 — Mark Leave dates (from-to inclusive)
            // Accepts single-date entry: if only one of l_from/l_to is set, treat as single day.
            $lFrom = $cfg['l_from'] ?? null;
            $lTo   = $cfg['l_to']   ?? null;
            if (!empty($lFrom) && empty($lTo)) $lTo = $lFrom;
            if (empty($lFrom) && !empty($lTo)) $lFrom = $lTo;
            if (!empty($lFrom) && !empty($lTo)) {
                $from = \Carbon\Carbon::parse($lFrom);
                $to   = \Carbon\Carbon::parse($lTo);
                if ($to->lt($from)) [$from, $to] = [$to, $from]; // swap if backward
                for ($dt = $from->copy(); $dt->lte($to); $dt->addDay()) {
                    if ($dt->year === $year && $dt->month === $month) {
                        $day = (int) $dt->format('j');
                        // Override Weekly Off — user explicitly picked this date as a leave
                        $statusByDay[$day] = 'On Leave';
                    }
                }
            }

            // Step 3 — Mark Absent dates (from-to inclusive). Same single-date fallback.
            $aFrom = $cfg['a_from'] ?? null;
            $aTo   = $cfg['a_to']   ?? null;
            if (!empty($aFrom) && empty($aTo)) $aTo = $aFrom;
            if (empty($aFrom) && !empty($aTo)) $aFrom = $aTo;
            if (!empty($aFrom) && !empty($aTo)) {
                $from = \Carbon\Carbon::parse($aFrom);
                $to   = \Carbon\Carbon::parse($aTo);
                if ($to->lt($from)) [$from, $to] = [$to, $from];
                for ($dt = $from->copy(); $dt->lte($to); $dt->addDay()) {
                    if ($dt->year === $year && $dt->month === $month) {
                        $day = (int) $dt->format('j');
                        // User's explicit absent date takes priority over auto-W/Off
                        $statusByDay[$day] = 'Absent';
                    }
                }
            }

            // Step 4 — Fill the rest as Present
            foreach ($statusByDay as $day => $status) {
                if ($status === null) $statusByDay[$day] = 'Present';
            }

            // Persist — one row per day for this employee
            foreach ($statusByDay as $day => $status) {
                $date = $dows[$day]->toDateString();
                AttendanceDaily::updateOrCreate(
                    ['emp_id' => $empId, 'attn_date' => $date],
                    [
                        'company_id'      => $emp->company_id,
                        'employee_name'   => $emp->full_name,
                        'shift_id'        => $emp->shift_id,
                        'shift_name'      => $emp->shift->shift_name ?? null,
                        'status'          => $status,
                        'source'          => 'summary',
                        'approval_status' => 'Approved',
                    ]
                );
                $totalCells++;
            }
            $employeesProcessed++;
        }

        return redirect()
            ->route('attendance.summary', ['year' => $year, 'month' => $month])
            ->with('status', "Saved attendance for {$employeesProcessed} employees ({$totalCells} day-cells written) for " . \DateTime::createFromFormat('!m', $month)->format('F') . " {$year}.");
    }

    /**
     * Workers-only Quick Counts — auto-filters to worker employee types and
     * adds a Contractor (Salary Group) dropdown. Workers are linked to a
     * contractor via salary_group_id (e.g. "Contractor - Lehar Singh Kitawat").
     *
     * Accepts BOTH 'WK' (legacy short code) and 'Worker' (current Excel-import
     * value) — see EmployeeImportSeeder typeLabel mapping.
     */
    public function countsWorkers(Request $req)
    {
        $req->merge(['emp_type' => 'Worker', '_workers_only' => 1]);
        return $this->counts($req);
    }

    /**
     * Move a worker (or any employee) to a different salary group.
     * Used by the inline "Move" button on /attendance/counts-workers.
     * Logs old/new group on the salary_group_id update; bypasses the model
     * to dodge any cast issues.
     */
    public function moveWorker(Request $req)
    {
        $req->validate([
            'emp_id'              => 'required|integer',
            'new_salary_group_id' => 'required|integer',
        ]);

        $emp = Employee::with('salary_group')->where('emp_id', $req->emp_id)->first();
        if (!$emp) return back()->with('status', '❌ Employee not found.');

        $newGroup = \App\Models\SalaryGroup::find($req->new_salary_group_id);
        if (!$newGroup) return back()->with('status', '❌ Target salary group not found.');

        $oldGroupName = $emp->salary_group->salary_group_name ?? '—';
        $oldGroupId   = $emp->salary_group_id;

        // Raw DB update — bypass model casts/observers to avoid silent failures
        try {
            $affected = \Illuminate\Support\Facades\DB::table('employees')
                ->where('emp_id', $req->emp_id)
                ->update([
                    'salary_group_id' => $req->new_salary_group_id,
                    'updated_at'      => now(),
                ]);
        } catch (\Throwable $ex) {
            \Log::error('moveWorker failed: ' . $ex->getMessage(), ['emp_id' => $req->emp_id, 'to_group' => $req->new_salary_group_id]);
            return back()->with('status', '❌ Move failed: ' . $ex->getMessage());
        }

        if ($affected === 0) {
            return back()->with('status', "⚠ No row updated for emp {$req->emp_id} — may be soft-deleted or locked.");
        }

        return back()->with('status',
            "✅ Moved {$emp->full_name} ({$emp->emp_id}) from [{$oldGroupId}] {$oldGroupName} → [{$newGroup->salary_group_id}] {$newGroup->salary_group_name}.");
    }

    /**
     * SUGAM HR-style "Quick Counts" entry — one row per employee, count-only fields.
     * No per-row date pickers. Fastest possible mode for typical monthly entry.
     */
    public function counts(Request $req)
    {
        $year  = (int) $req->input('year',  now()->year);
        $month = (int) $req->input('month', now()->month);
        $cid   = (int) session('active_company_id', 0);

        $totalDays = (int) \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $sundays = 0; $saturdays = 0;
        for ($d = 1; $d <= $totalDays; $d++) {
            $dt = \Carbon\Carbon::createFromDate($year, $month, $d);
            if ($dt->dayOfWeek === 0) $sundays++;
            if ($dt->dayOfWeek === 6) $saturdays++;
        }

        $empQ = Employee::with(['department','salary_group'])->where('active_flag', true);

        // Search behaviour:
        //   1. Find the matching employees (name/ID/TP/father — case-insensitive).
        //   2. Expand the result to ALL employees in those matches' contractors,
        //      so HR can edit attendance for the whole team in one view.
        if ($req->filled('q')) {
            $q = trim((string) $req->q);
            $matchingGroupIds = Employee::where('active_flag', true)
                ->when($req->filled('emp_type'), fn($x) => $x->whereIn('employee_type', $this->expandEmpType($req->emp_type)))
                ->where(function ($w) use ($q) {
                    $w->whereRaw('LOWER(full_name) LIKE ?',         ['%'.strtolower($q).'%'])
                      ->orWhere('emp_id', 'like', "%$q%")
                      ->orWhere('third_party_code', 'like', "%$q%")
                      ->orWhereRaw('LOWER(fathers_name) LIKE ?',    ['%'.strtolower($q).'%']);
                })
                ->pluck('salary_group_id')->filter()->unique()->values()->all();

            if (count($matchingGroupIds) === 0) {
                // No matches at all — return empty paginator
                $empQ->whereRaw('1 = 0');
            } else {
                $empQ->whereIn('salary_group_id', $matchingGroupIds);
                if ($req->filled('emp_type')) $empQ->whereIn('employee_type', $this->expandEmpType($req->emp_type));
            }
        } else {
            // No search → apply all the dropdown filters INCLUDING active company.
            // The header company switcher must scope every page to that company.
            if ($cid)                            $empQ->where('company_id', $cid);
            if ($req->filled('dept_id'))         $empQ->where('dept_id', $req->dept_id);
            if ($req->filled('emp_type'))        $empQ->whereIn('employee_type', $this->expandEmpType($req->emp_type));
            if ($req->filled('salary_group_id')) $empQ->where('salary_group_id', $req->salary_group_id);
        }
        // Sort by salary_group_id first so contractor's workers stay together
        $employees = $empQ->orderBy('salary_group_id')->orderBy('emp_id')->paginate(200)->appends($req->query());

        // Set workers-only flag NOW so debug block can reference it
        $workersOnly = $req->boolean('_workers_only');

        // ── ?debug=1 — dumps exactly what the search produced. Add this query
        //    string to ANY counts URL to see the diagnosis inline. ──
        if ($req->boolean('debug')) {
            $debugQ = $req->q;
            $matches = !empty($debugQ)
                ? Employee::where('active_flag', true)
                    ->when($req->filled('emp_type'), fn($x) => $x->whereIn('employee_type', $this->expandEmpType($req->emp_type)))
                    ->where(function ($w) use ($debugQ) {
                        $lc = strtolower(trim((string) $debugQ));
                        $w->whereRaw('LOWER(full_name) LIKE ?', ["%$lc%"])
                          ->orWhere('emp_id', 'like', "%$debugQ%")
                          ->orWhere('third_party_code', 'like', "%$debugQ%")
                          ->orWhereRaw('LOWER(fathers_name) LIKE ?', ["%$lc%"]);
                    })
                    ->get(['emp_id','full_name','employee_type','active_flag','salary_group_id','company_id'])
                : collect();

            // Diagnostic — what's actually in the employees table?
            $totalEmps    = Employee::count();
            $activeEmps   = Employee::where('active_flag', true)->count();
            $typeBreakdown = Employee::selectRaw('employee_type, COUNT(*) as cnt')
                ->groupBy('employee_type')->orderByDesc('cnt')->get();
            $sampleWorker = Employee::whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(employee_type)'), ['wk','w','worker','labour','labor','contract'])
                ->first(['emp_id','full_name','employee_type','active_flag','salary_group_id']);
            // Workers identified via salary_group (Contractor groups)
            $contractorIds = \App\Models\SalaryGroup::where('salary_group_name','like','%ontractor%')
                ->orWhere('salary_group_name','like','%abour%')
                ->pluck('salary_group_id')->all();
            $viaGroupCount = $contractorIds
                ? Employee::whereIn('salary_group_id', $contractorIds)->where('active_flag', true)->count()
                : 0;

            return response('<pre style="font-family:monospace;font-size:13px;padding:20px;line-height:1.5">'
                . "🔍 DEBUG — counts() search\n\n"
                . "Request:\n"
                . "  q (search)         : " . var_export($req->q, true) . "\n"
                . "  emp_type           : " . var_export($req->emp_type, true) . "\n"
                . "  salary_group_id    : " . var_export($req->salary_group_id, true) . "\n"
                . "  _workers_only flag : " . var_export($workersOnly, true) . "\n"
                . "  active_company_id  : {$cid}\n"
                . "  year/month         : {$year}/{$month}\n\n"
                . "Database state:\n"
                . "  Total employees    : {$totalEmps}\n"
                . "  Active employees   : {$activeEmps}\n"
                . "  Workers via Contractor salary_group (active_flag=true) : {$viaGroupCount}\n\n"
                . "Distinct employee_type values in DB (with row counts):\n"
                . $typeBreakdown->map(fn($t) => "  → " . var_export($t->employee_type, true) . " : {$t->cnt} row(s)")->implode("\n") . "\n\n"
                . ($sampleWorker
                    ? "Sample worker-like employee:\n"
                      . "  → emp_id={$sampleWorker->emp_id} '{$sampleWorker->full_name}'\n"
                      . "    employee_type=" . var_export($sampleWorker->employee_type, true)
                      . " active=" . ($sampleWorker->active_flag?'Y':'N')
                      . " group_id={$sampleWorker->salary_group_id}\n\n"
                    : "❌ NO worker-like rows found by employee_type fuzzy match.\n\n")
                . "Direct match query (active_flag + emp_type='WK' + name LIKE q):\n"
                . "  Returned " . $matches->count() . " row(s)\n"
                . $matches->map(fn($e) => "    → emp_id={$e->emp_id} '{$e->full_name}' "
                                       . "type={$e->employee_type} active=" . ($e->active_flag?'Y':'N')
                                       . " group_id={$e->salary_group_id} company_id={$e->company_id}")->implode("\n") . "\n\n"
                . "Page query result: " . $employees->total() . " total row(s) (page " . $employees->currentPage() . ")\n"
                . $employees->map(fn($e) => "    → {$e->emp_id} {$e->full_name} (group={$e->salary_group_id})")->implode("\n") . "\n\n"
                . "─────────────────────────────────────────────\n"
                . "DIAGNOSIS:\n"
                . "  • If 'employee_type' breakdown shows a value other than 'WK' (e.g. 'Worker', 'wk', 'WORKER'),\n"
                . "    that's the mismatch. The page filters strictly for 'WK'.\n"
                . "  • If everyone is 'ST' or no 'WK' shown, your seeders set them differently.\n"
                . "</pre>'");
        }

        // Contractor list for the workers-only page (salary groups whose name
        // starts with "Contractor"). Scoped to active company so only the
        // current company's contractors / labour groups appear in the dropdown.
        // Contractor list for the workers-only page. Matches "Contractor" /
        // "Contarctor" (legacy typo) / "Labour" / "Labor" anywhere in the name
        // — at the start, middle, or end — so users can name groups freely
        // (e.g. "Gajendra Singh Kitawat Contractor" still shows up here).
        $contractors = $workersOnly
            ? \App\Models\SalaryGroup::when($cid, fn($q) => $q->where('company_id', $cid))
                ->where(function ($q) {
                    $q->where('salary_group_name', 'like', '%Contractor%')
                      ->orWhere('salary_group_name', 'like', '%Contarctor%')   // tolerate legacy typo
                      ->orWhere('salary_group_name', 'like', '%Labour%')
                      ->orWhere('salary_group_name', 'like', '%Labor%');
                })
                ->orderBy('salary_group_name')->get()
            : collect();

        $empIds = $employees->pluck('emp_id')->all();

        // 1) Prefer the SAVED RAW COUNTS from attendance_summary (preserves exact
        //    decimals like 25.5 P + 0.5 CL). 2) Fall back to deriving from
        //    attendance_daily for periods not yet entered via /attendance/counts.
        $existing = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('attendance_summary')) {
            $rows = \App\Models\AttendanceSummary::whereIn('emp_id', $empIds)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->get();
            // Build a plain (base) Collection so merge() works with arrays
            $existing = collect();
            foreach ($rows as $s) {
                $existing[$s->emp_id] = [
                    'p'  => (float) $s->p_count,
                    'w'  => (float) $s->w_count,
                    'cl' => (float) $s->cl_count,
                    'sl' => (float) $s->sl_count,
                    'pl' => (float) $s->pl_count,
                    'a'  => (float) $s->a_count,
                    'hd' => (float) $s->hd_count,
                    'ph' => (float) ($s->ph_count ?? 0),
                    'ot' => (float) $s->ot_hours,
                ];
            }
        }

        // For employees who have NO attendance_summary row, derive from daily
        $missingIds = array_diff($empIds, $existing->keys()->all());
        if (!empty($missingIds)) {
            $start  = \Carbon\Carbon::createFromDate($year, $month, 1)->toDateString();
            $end    = \Carbon\Carbon::createFromDate($year, $month, $totalDays)->toDateString();
            $rowsByEmp = AttendanceDaily::whereIn('emp_id', $missingIds)
                ->whereBetween('attn_date', [$start, $end])
                ->get()
                ->groupBy('emp_id');
            foreach ($rowsByEmp as $empIdKey => $rows) {
                $c = ['p'=>0,'w'=>0,'cl'=>0,'sl'=>0,'pl'=>0,'a'=>0,'hd'=>0,'ph'=>0,'ot'=>0];
                foreach ($rows as $r) {
                    $s = strtolower((string) $r->status);
                    if ($s === 'present')        $c['p']++;
                    elseif ($s === 'weekly off')  $c['w']++;
                    elseif ($s === 'half day')    $c['hd']++;
                    elseif ($s === 'absent')      $c['a']++;
                    elseif ($s === 'on leave') {
                        $sub = strtoupper(substr((string)$r->status_reason, 0, 2));
                        if ($sub === 'CL') $c['cl']++;
                        elseif ($sub === 'SL') $c['sl']++;
                        elseif ($sub === 'PL') $c['pl']++;
                        else $c['cl']++;
                    }
                    $c['ot'] += (float) ($r->ot_hours ?? 0);
                }
                $existing[$empIdKey] = $c;
            }
        }

        // ── DEFAULT for employees still empty (no summary AND no daily entries) ──
        // Pre-fill with the standard month pattern: (TotalDays − Sundays) Present
        // + Sundays as Weekly Off. April 2026 → 26 P + 4 W. Saves manual entry
        // for employees with normal full-month attendance.
        $defaultP = $totalDays - $sundays;
        $defaultW = $sundays;
        foreach ($empIds as $empIdKey) {
            if (!$existing->has($empIdKey)) {
                $existing[$empIdKey] = [
                    'p'  => $defaultP,
                    'w'  => $defaultW,
                    'cl' => 0,
                    'sl' => 0,
                    'pl' => 0,
                    'a'  => 0,
                    'hd' => 0,
                    'ph' => 0,
                    'ot' => 0,
                ];
            }
        }

        $departments = \App\Models\Department::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('dept_name')->get();

        return view('attendance.counts', compact(
            'employees', 'existing', 'year', 'month', 'totalDays',
            'sundays', 'saturdays', 'departments', 'workersOnly', 'contractors'
        ));
    }

    /**
     * Save SUGAM-style counts: distribute the counts across actual days of month.
     */
    public function countsSave(Request $req)
    {
        $year  = (int) $req->input('year');
        $month = (int) $req->input('month');
        $rows  = $req->input('row', []); // row[empId] = ['p'=>25,'w'=>4,'cl'=>1,'sl'=>0,'pl'=>0,'a'=>0,'hd'=>0,'ot'=>10]

        $totalDays = (int) \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

        // Pre-build day-of-week map
        $dows = [];
        for ($d = 1; $d <= $totalDays; $d++) {
            $dows[$d] = \Carbon\Carbon::createFromDate($year, $month, $d);
        }
        $sundayDays = collect($dows)->filter(fn($dt) => $dt->dayOfWeek === 0)->keys()->all();
        $saturdayDays = collect($dows)->filter(fn($dt) => $dt->dayOfWeek === 6)->keys()->all();

        $employeesProcessed = 0;
        $totalCells = 0;
        $errors = [];

        foreach ($rows as $empId => $cfg) {
            $emp = Employee::where('emp_id', $empId)->first();
            if (!$emp) continue;

            $p  = (float) ($cfg['p']  ?? 0);
            $w  = (float) ($cfg['w']  ?? 0);
            $cl = (float) ($cfg['cl'] ?? 0);
            $sl = (float) ($cfg['sl'] ?? 0);
            $pl = (float) ($cfg['pl'] ?? 0);
            $a  = (float) ($cfg['a']  ?? 0);
            $hd = (float) ($cfg['hd'] ?? 0);
            $ph = (float) ($cfg['ph'] ?? 0);
            $ot = (float) ($cfg['ot'] ?? 0);

            // If totally empty row, skip
            if (($p + $w + $cl + $sl + $pl + $a + $hd + $ph) <= 0) continue;

            // Validate total — allow tiny float drift
            $sum = round($p + $w + $cl + $sl + $pl + $a + $hd + $ph, 2);
            if (abs($sum - $totalDays) > 0.001) {
                $errors[] = "Emp {$empId} ({$emp->full_name}): total = {$sum}, expected {$totalDays}";
                continue;
            }

            // ── PRESERVE the user's exact entry in attendance_summary ──
            //    so reloading this page shows 25.5 / 4 / 0.5 verbatim, not a
            //    re-derived approximation.
            if (\Illuminate\Support\Facades\Schema::hasTable('attendance_summary')) {
                $payload = [
                    'company_id'  => $emp->company_id,
                    'p_count'     => $p,
                    'w_count'     => $w,
                    'cl_count'    => $cl,
                    'sl_count'    => $sl,
                    'pl_count'    => $pl,
                    'a_count'     => $a,
                    'hd_count'    => $hd,
                    'ot_hours'    => $ot,
                    'total_days'  => $sum,
                    'created_by_user_id' => auth()->id(),
                ];
                if (\Illuminate\Support\Facades\Schema::hasColumn('attendance_summary', 'ph_count')) {
                    $payload['ph_count'] = $ph;
                }
                \App\Models\AttendanceSummary::updateOrCreate(
                    ['emp_id' => $emp->emp_id, 'period_year' => $year, 'period_month' => $month],
                    $payload
                );
            }

            $statusByDay = array_fill_keys(range(1, $totalDays), null);
            $reasonByDay = array_fill_keys(range(1, $totalDays), null);

            // ── Decompose float counts into whole-day buckets + half-day fragments ──
            // Each 0.5 fragment is one half-unit. Two half-units fit on ONE calendar
            // day (paired AM+PM). E.g.:
            //   25.5P + 4W + 0.5CL = 30
            //     → 25 full P + 4 W + 0 full CL + 1 calendar day (P + CL halves)
            //     → 30 calendar entries total ✓
            $fragments = [];   // list of reason codes for half-units
            $whole = [];       // ['p' => 25, 'w' => 4, 'cl' => 0, ...]
            foreach ([
                'p'  => [$p,  'P'],
                'w'  => [$w,  'W'],
                'cl' => [$cl, 'CL'],
                'sl' => [$sl, 'SL'],
                'pl' => [$pl, 'PL'],
                'a'  => [$a,  'A'],
            ] as $key => [$count, $reason]) {
                $w_int = (int) floor($count + 1e-9);
                $frac  = $count - $w_int;
                $whole[$key] = $w_int;
                if ($frac > 0.001) {
                    $fragments[] = $reason;
                }
            }
            $hdWhole = (int) floor($hd + 1e-9);
            $hdFrac  = $hd - $hdWhole;
            if ($hdFrac > 0.001) $fragments[] = 'HD';

            // Pair fragments onto calendar days: 2 fragments = 1 calendar day with
            // status "Half Day". Odd fragment goes alone.
            $halfDayCells = (int) ceil(count($fragments) / 2);
            $halfDayReasons = [];
            for ($i = 0; $i < count($fragments); $i += 2) {
                $halfDayReasons[] = isset($fragments[$i + 1])
                    ? $fragments[$i] . '/' . $fragments[$i + 1]
                    : $fragments[$i] . ' Half';
            }

            // Step 1 — W/Off: Sundays first, then Saturdays
            $woAssigned = 0;
            foreach ($sundayDays as $day) {
                if ($woAssigned >= $whole['w']) break;
                $statusByDay[$day] = 'Weekly Off';
                $woAssigned++;
            }
            if ($woAssigned < $whole['w']) {
                foreach ($saturdayDays as $day) {
                    if ($woAssigned >= $whole['w']) break;
                    if ($statusByDay[$day] === null) {
                        $statusByDay[$day] = 'Weekly Off';
                        $woAssigned++;
                    }
                }
            }

            // Helper: assign next N free days as $status with optional reason
            $assignNext = function (int $count, string $status, ?string $reason) use (&$statusByDay, &$reasonByDay, $totalDays) {
                $assigned = 0;
                for ($d = 1; $d <= $totalDays && $assigned < $count; $d++) {
                    if ($statusByDay[$d] === null) {
                        $statusByDay[$d] = $status;
                        if ($reason) $reasonByDay[$d] = $reason;
                        $assigned++;
                    }
                }
                return $assigned;
            };

            // Step 2 — CL/SL/PL leaves (whole days only; fractions handled below)
            $assignNext($whole['cl'], 'On Leave', 'CL');
            $assignNext($whole['sl'], 'On Leave', 'SL');
            $assignNext($whole['pl'], 'On Leave', 'PL');

            // Step 3 — Absent (whole days)
            $assignNext($whole['a'], 'Absent', null);

            // Step 4 — Half Day cells: explicit HD count + fragment-pair rollups
            //   (assign from end of month going backward so half-days land late)
            $hdTotal = $hdWhole + $halfDayCells;
            $hdAssigned = 0;
            $reasonIdx = 0;
            for ($d = $totalDays; $d >= 1 && $hdAssigned < $hdTotal; $d--) {
                if ($statusByDay[$d] === null) {
                    $statusByDay[$d] = 'Half Day';
                    if ($reasonIdx < count($halfDayReasons)) {
                        $reasonByDay[$d] = $halfDayReasons[$reasonIdx++];
                    }
                    $hdAssigned++;
                }
            }

            // Step 5 — Present fills the rest
            foreach ($statusByDay as $day => $status) {
                if ($status === null) $statusByDay[$day] = 'Present';
            }

            // Step 6 — distribute OT hours evenly across Present days
            $presentDays = collect($statusByDay)->filter(fn($s) => $s === 'Present')->keys()->all();
            $otPerDay = count($presentDays) > 0 ? round($ot / count($presentDays), 2) : 0;

            // Persist
            foreach ($statusByDay as $day => $status) {
                $date = $dows[$day]->toDateString();
                $payload = [
                    'company_id'      => $emp->company_id,
                    'employee_name'   => $emp->full_name,
                    'shift_id'        => $emp->shift_id,
                    'shift_name'      => $emp->shift->shift_name ?? null,
                    'status'          => $status,
                    'status_reason'   => $reasonByDay[$day],
                    'source'          => 'counts',
                    'approval_status' => 'Approved',
                ];
                if ($status === 'Present' && $otPerDay > 0) {
                    $payload['ot_hours'] = $otPerDay;
                }
                AttendanceDaily::updateOrCreate(
                    ['emp_id' => $empId, 'attn_date' => $date],
                    $payload
                );
                $totalCells++;
            }
            $employeesProcessed++;
        }

        $rowsReceived = count($rows);
        $msg = "✅ Saved counts for {$employeesProcessed} of {$rowsReceived} employees ({$totalCells} day-cells written).";
        if (!empty($errors)) {
            $msg .= ' Skipped ' . count($errors) . ' rows with mismatched totals: ' . implode('; ', array_slice($errors, 0, 3))
                  . (count($errors) > 3 ? '...' : '');
        }
        if ($rowsReceived === 0) {
            $msg = "⚠️ Form submitted but NO row data received. Check the form's input fields are named row[empId][p], row[empId][w], etc.";
        }

        // Preserve the page context the user came from (workers vs all)
        $cameFromWorkers = $req->input('_workers_only') || str_contains((string) $req->header('referer', ''), 'counts-workers');
        $routeName = $cameFromWorkers ? 'attendance.counts-workers' : 'attendance.counts';
        $routeParams = ['year' => $year, 'month' => $month];
        // Preserve filters (salary_group_id, q, dept_id) so the user lands back on the same view
        if ($req->filled('salary_group_id')) $routeParams['salary_group_id'] = $req->salary_group_id;
        if ($req->filled('q'))               $routeParams['q']               = $req->q;
        if ($req->filled('dept_id'))         $routeParams['dept_id']         = $req->dept_id;

        return redirect()->route($routeName, $routeParams)->with('status', $msg);
    }

    public function viewReporting()
    {
        $employees = Employee::where('active_flag', true)
            ->with(['department', 'designation'])
            ->orderBy('dept_id')
            ->orderBy('full_name')
            ->take(500)->get();
        return view('attendance.view-reporting', compact('employees'));
    }

    public function tour()
    {
        return view('attendance.tour-od');
    }

    public function leaveCreate()
    {
        $cid = (int) session('active_company_id', 0);
        $employees = Employee::where('active_flag', true)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->orderBy('full_name')
            ->get(['emp_id', 'full_name']);
        $leaveTypes = LeaveType::where('active_flag', true)->orderBy('leave_code')->get();
        return view('leave.create', compact('employees', 'leaveTypes'));
    }

    public function leaveStore(Request $req)
    {
        $req->validate([
            'emp_id'        => 'required|integer',
            'leave_type_id' => 'required|integer',
            'from_date'     => 'required|date',
            'to_date'       => 'required|date|after_or_equal:from_date',
            'reason'        => 'nullable|string|max:500',
        ]);

        $emp  = Employee::find($req->emp_id);
        if (!$emp) return back()->with('status', 'Employee not found.');
        $type = LeaveType::find($req->leave_type_id);

        $from = \Carbon\Carbon::parse($req->from_date);
        $to   = \Carbon\Carbon::parse($req->to_date);
        $days = $from->diffInDays($to) + 1;

        LeaveApplication::create([
            'company_id'      => $emp->company_id,
            'emp_id'          => $emp->emp_id,
            'employee_name'   => $emp->full_name,
            'leave_type_id'   => $type?->leave_type_id,
            'leave_code'      => $type?->leave_code,
            'from_date'       => $from->toDateString(),
            'to_date'         => $to->toDateString(),
            'days'            => $days,
            'half_day_flag'   => $req->boolean('half_day_flag'),
            'reason'          => $req->reason,
            'applied_at'      => now(),
            'approval_status' => 'Pending',
            'active_flag'     => true,
        ]);

        return redirect()->route('leave.record')
            ->with('status', "Leave application submitted for {$emp->full_name} ({$days} day" . ($days > 1 ? 's' : '') . "). Awaiting approval.");
    }

    public function leaveOnline()
    {
        $pending = LeaveApplication::where('status', 'Pending')
            ->orderByDesc('applied_at')
            ->paginate(50);
        return view('leave.online', compact('pending'));
    }

    public function leaveApprove($id)
    {
        $app = LeaveApplication::findOrFail($id);
        $app->update(['status' => 'Approved', 'approval_date' => now()]);
        return back()->with('status', 'Leave approved.');
    }

    public function leaveReject($id)
    {
        $app = LeaveApplication::findOrFail($id);
        $app->update(['status' => 'Rejected']);
        return back()->with('status', 'Leave rejected.');
    }

    public function balance()
    {
        return app(LeaveBalanceController::class)->index(request());
    }

    public function record(Request $req)
    {
        $cid = (int) session('active_company_id', 0);
        $q = LeaveApplication::query();
        if ($cid) $q->where('company_id', $cid);
        if ($req->filled('status')) $q->where('approval_status', $req->status);
        if ($req->filled('emp_id'))  $q->where('emp_id', $req->emp_id);
        $records = $q->orderByDesc('applied_at')->paginate(50)->appends($req->query());

        $totals = [
            'total'    => (clone $q)->count(),
            'pending'  => (clone $q)->where('approval_status', 'Pending')->count(),
            'approved' => (clone $q)->where('approval_status', 'Approved')->count(),
            'rejected' => (clone $q)->where('approval_status', 'Rejected')->count(),
        ];
        return view('leave.record', compact('records', 'totals'));
    }
}
