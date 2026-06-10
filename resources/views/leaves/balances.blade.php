@extends('layouts.app')
@section('title', 'Leave Balances')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Leaves / <span class="text-slate-900 font-semibold">Leave Balances</span></div>

    @php
        $fyLabel = ($fy - 1) . '-' . substr((string)$fy, -2);    // "2026-27"
        $fyStart = ($fy - 1) . '-04-01';                          // "2026-04-01"
        $fyEnd   = $fy       . '-03-31';                          // "2027-03-31"
    @endphp
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-bold">Leave Balances <span class="text-sm font-normal text-slate-500">— FY {{ $fyLabel }}</span></h1>
            <div class="text-xs text-slate-500 mt-1">
                Period: <strong>1-Apr-{{ $fy-1 }}</strong> to <strong>31-Mar-{{ $fy }}</strong> &nbsp;•&nbsp;
                Opening = carry-forward from 31-Mar-{{ $fy-1 }}
            </div>
        </div>
        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('leaves.balances.import') }}" class="inline"
                  onsubmit="return confirm('Re-import the 31-Mar-2026 closing balances from CSV?\nThese become the opening for FY 2026-27.\nExisting FY {{ $fyLabel }} rows will be refreshed (availed YTD reset to 0).')">
                @csrf
                <button class="tb-btn primary" style="background:#7C3AED;border-color:#6D28D9">⬆ Import 31-Mar-2026 CSV</button>
            </form>
            <a href="{{ route('leaves.balances', ['fy' => $fy, 'salary_group_id' => $salaryGroupId, 'format' => 'csv']) }}"
               class="tb-btn primary" style="background:#16A34A;border-color:#15803D">⬇ CSV</a>
            <a href="{{ route('leaves.balances', ['fy' => $fy, 'salary_group_id' => $salaryGroupId, 'format' => 'xls']) }}"
               class="tb-btn primary" style="background:#0EA5E9;border-color:#0284C7">⬇ Excel</a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    {{-- Diagnostic strip: shows what's actually in leave_balances for this FY,
         so it's obvious whether the import ran and which filter is hiding data. --}}
    <div class="mb-3 rounded-lg bg-blue-50 border border-blue-200 px-3 py-2 text-sm text-blue-900 flex items-center gap-4 flex-wrap">
        <div><strong>{{ $totalForFy }}</strong> total leave_balances row(s) in FY {{ $fyLabel }}.</div>
        <div><strong>{{ $rows->count() }}</strong> employee(s) shown after group filter.</div>
        @if($totalForFy === 0)
            <div class="text-red-700 font-semibold">⚠ No data imported yet — click <em>Import 31-Mar-2026 CSV</em> above.</div>
        @elseif($rows->isEmpty() && $totalForFy > 0)
            <div class="text-amber-700 font-semibold">⚠ Data is loaded but the current group filter hides it. Reset Salary Group to "— All Groups —" and click Apply.</div>
        @endif
    </div>

    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Financial Year</label>
            <select name="fy" class="border border-[var(--line)] rounded p-2 text-sm" style="width:130px">
                @foreach([2025, 2026, 2027, 2028] as $opt)
                    <option value="{{ $opt }}" @selected($opt == $fy)>{{ $opt-1 }}-{{ substr($opt, -2) }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:240px">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Salary Group</label>
            <select name="salary_group_id" class="border border-[var(--line)] rounded p-2 text-sm w-full">
                <option value="0">— All Groups —</option>
                @foreach($salaryGroups as $g)
                    <option value="{{ $g->salary_group_id }}" @selected($salaryGroupId == $g->salary_group_id)>{{ $g->salary_group_name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="tb-btn primary">Apply</button>
    </form>

    <div class="text-xs text-slate-500 mb-3">
        Closing balance = Opening + Accrued − Availed YTD. Balances update automatically each time payroll runs for a month (CL/PL/SL taken in attendance is consumed from the balance).
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    <th rowspan="2">Emp ID</th>
                    <th rowspan="2">Name</th>
                    <th rowspan="2">Salary Group</th>
                    <th colspan="3" class="text-center" style="background:#FEF3C7">Casual Leave (CL)</th>
                    <th colspan="3" class="text-center" style="background:#DBEAFE">Privilege Leave (PL)</th>
                    <th colspan="3" class="text-center" style="background:#FCE7F3">Sick Leave (SL)</th>
                    <th rowspan="2"></th>
                </tr>
                <tr>
                    <th class="text-right">Opening</th><th class="text-right">Availed</th><th class="text-right">Closing</th>
                    <th class="text-right">Opening</th><th class="text-right">Availed</th><th class="text-right">Closing</th>
                    <th class="text-right">Opening</th><th class="text-right">Availed</th><th class="text-right">Closing</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $fmt = fn($v) => rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.') ?: '0';
                @endphp
                @forelse($rows as $r)
                    <tr>
                        <td class="font-mono text-xs">{{ $r['emp_id'] }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td class="text-xs">{{ $r['group'] }}</td>
                        <td class="text-right">{{ $fmt($r['cl_opening']) }}</td>
                        <td class="text-right">{{ $fmt($r['cl_availed']) }}</td>
                        <td class="text-right font-bold">{{ $fmt($r['cl_closing']) }}</td>
                        <td class="text-right">{{ $fmt($r['pl_opening']) }}</td>
                        <td class="text-right">{{ $fmt($r['pl_availed']) }}</td>
                        <td class="text-right font-bold">{{ $fmt($r['pl_closing']) }}</td>
                        <td class="text-right">{{ $fmt($r['sl_opening']) }}</td>
                        <td class="text-right">{{ $fmt($r['sl_availed']) }}</td>
                        <td class="text-right font-bold">{{ $fmt($r['sl_closing']) }}</td>
                        <td class="text-center">
                            <a href="{{ route('leaves.balances.edit', ['empId' => $r['emp_id'], 'fy' => $fy]) }}"
                               class="text-indigo-600 hover:underline text-xs">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="text-center py-6 text-slate-500">
                        No leave-balance records for FY {{ $fy-1 }}-{{ substr($fy, -2) }}{{ $salaryGroupId ? ' in selected group' : '' }}.
                        <br><span class="text-xs">Click "Import 31-Mar-2026 CSV" above to seed the balances.</span>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
