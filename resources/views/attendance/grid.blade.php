@extends('layouts.app')
@section('title', 'Bulk Attendance Grid')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Attendance & Leave / <span class="text-slate-900 font-semibold">Bulk Grid</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Bulk Attendance &mdash; {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h1>
        <a href="{{ route('attendance.daily', ['date' => $year.'-'.str_pad($month,2,'0',STR_PAD_LEFT).'-01']) }}" class="tb-btn">View Daily</a>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    {{-- Period + filter form --}}
    <form method="GET" class="card p-3 mb-3 flex flex-wrap gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
            <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                    <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                @endforeach
            </select></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Department</label>
            <select name="dept_id" class="border border-[var(--line)] rounded p-2 text-sm" style="min-width:160px">
                <option value="">All</option>
                @foreach($departments as $d)
                    <option value="{{ $d->dept_id }}" @selected(request('dept_id') == $d->dept_id)>{{ $d->dept_name }}</option>
                @endforeach
            </select></div>
        <div class="flex-1"><label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Emp ID or name…" class="border border-[var(--line)] rounded p-2 text-sm w-full"/></div>
        <button type="submit" class="tb-btn primary">Apply</button>
    </form>

    {{-- Legend --}}
    <div class="card p-3 mb-3 flex flex-wrap gap-3 items-center text-xs">
        <span class="font-semibold">Status codes:</span>
        @foreach([['P','Present','#10B981'],['A','Absent','#EF4444'],['L','On Leave','#F59E0B'],['D','On Duty','#3B82F6'],['H','Half Day','#F97316'],['W','Weekly Off','#94A3B8'],['F','Holiday','#A855F7']] as [$c,$lbl,$col])
            <span class="inline-flex items-center gap-1"><span class="inline-block w-5 h-5 rounded text-white text-center font-bold leading-5" style="background:{{ $col }}">{{ $c }}</span> {{ $lbl }}</span>
        @endforeach
    </div>

    {{-- Quick-action toolbar --}}
    <div class="card p-3 mb-3">
        <div class="font-semibold text-sm mb-2">Quick Actions</div>

        {{-- Date-range marker --}}
        <div class="flex flex-wrap gap-2 items-end mb-3 pb-3 border-b border-[var(--line)]">
            <span class="text-xs font-semibold text-slate-600 mr-2">Mark range:</span>
            <div><label class="block text-[10px] text-slate-500 mb-1">Employee</label>
                <select id="rangeEmp" class="border border-[var(--line)] rounded p-1.5 text-xs" style="min-width:160px">
                    <option value="">All visible employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->emp_id }}">{{ $e->emp_id }} — {{ $e->full_name }}</option>
                    @endforeach
                </select></div>
            <div><label class="block text-[10px] text-slate-500 mb-1">From day</label>
                <input id="rangeFrom" type="number" min="1" max="{{ $totalDays }}" value="1" class="border border-[var(--line)] rounded p-1.5 text-xs" style="width:60px"></div>
            <div><label class="block text-[10px] text-slate-500 mb-1">To day</label>
                <input id="rangeTo" type="number" min="1" max="{{ $totalDays }}" value="{{ $totalDays }}" class="border border-[var(--line)] rounded p-1.5 text-xs" style="width:60px"></div>
            <div><label class="block text-[10px] text-slate-500 mb-1">Status</label>
                <select id="rangeStatus" class="border border-[var(--line)] rounded p-1.5 text-xs">
                    <option value="P">P · Present</option>
                    <option value="L" selected>L · On Leave</option>
                    <option value="A">A · Absent</option>
                    <option value="D">D · On Duty</option>
                    <option value="H">H · Half Day</option>
                    <option value="W">W · Weekly Off</option>
                    <option value="F">F · Holiday</option>
                    <option value="">— Clear —</option>
                </select></div>
            <button type="button" onclick="applyRange()" class="tb-btn primary">Apply to range</button>
            <span class="text-[11px] text-slate-500 ml-2">e.g., Emp 13002, From 2, To 10, Status L → marks Apr 2–10 as On Leave</span>
        </div>

        {{-- Bulk-fill --}}
        <div class="flex flex-wrap gap-2 items-center">
            <span class="text-xs font-semibold text-slate-600 mr-2">Bulk:</span>
            <button type="button" onclick="fillAll('P')" class="tb-btn">Fill All Present</button>
            <button type="button" onclick="fillEmpty('P')" class="tb-btn">Fill empty as Present</button>
            <button type="button" onclick="fillSundays('W')" class="tb-btn">Sundays → Weekly Off</button>
            <button type="button" onclick="autoFillRest('P','W')" class="tb-btn primary">⚡ Auto-fill: empty=Present + Sun=W/Off</button>
            <button type="button" onclick="fillAll('')" class="tb-btn">Clear All</button>
        </div>
    </div>

    <form method="POST" action="{{ route('attendance.grid.save') }}" id="gridForm">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">

        <div class="card overflow-x-auto" style="max-width:100%">
            <table class="grid-tbl" style="border-collapse:separate;border-spacing:0;font-size:11px">
                <thead>
                    <tr>
                        <th style="position:sticky;left:0;z-index:3;background:#F1F5F9;min-width:60px">Emp ID</th>
                        <th style="position:sticky;left:60px;z-index:3;background:#F1F5F9;min-width:160px">Name</th>
                        <th style="position:sticky;left:220px;z-index:3;background:#F1F5F9;min-width:90px">Dept</th>
                        @foreach($dayLabels as $d => $info)
                            <th style="min-width:32px;text-align:center;{{ $info['is_sun'] ? 'background:#FEE2E2;color:#991B1B' : '' }}">
                                {{ $d }}<br><span class="font-normal text-[10px]">{{ $info['short'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $e)
                        <tr>
                            <td style="position:sticky;left:0;background:#fff;z-index:2;font-weight:600">{{ $e->emp_id }}</td>
                            <td style="position:sticky;left:60px;background:#fff;z-index:2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px">{{ $e->full_name }}</td>
                            <td style="position:sticky;left:220px;background:#fff;z-index:2;font-size:10px;color:#64748B">{{ $e->department->dept_name ?? '—' }}</td>
                            @foreach($dayLabels as $d => $info)
                                @php $existing_status = $existing[$e->emp_id][$d]->status ?? null; @endphp
                                <td style="padding:1px;text-align:center">
                                    <select name="cell[{{ $e->emp_id }}][{{ $d }}]" class="grid-cell" data-day="{{ $d }}" data-dow="{{ $info['dow'] }}"
                                            style="border:1px solid #E2E8F0;border-radius:3px;padding:2px;font-size:10px;width:30px;text-align:center;font-weight:bold">
                                        <option value="">—</option>
                                        @foreach(['P','A','L','D','H','W','F'] as $c)
                                            <option value="{{ $c }}" @selected($existing_status && substr(strtoupper($existing_status),0,1) === $c)>{{ $c }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($dayLabels) + 3 }}" class="text-center py-6 text-slate-500">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif

        <div class="sticky bottom-0 z-10 bg-white border-t border-[var(--line)] py-3 px-4 mt-3 flex justify-between items-center">
            <div class="text-xs text-slate-500">Tip: change a cell, then Tab to move to the next day.</div>
            <button type="submit" class="tb-btn primary" style="font-size:14px;padding:10px 20px">💾 Save All Attendance</button>
        </div>
    </form>
</div>

<style>
    .grid-cell:focus { outline: 2px solid var(--brand); outline-offset: -1px; }
    select.grid-cell option[value="P"] { background: #D1FAE5 }
    select.grid-cell option[value="A"] { background: #FEE2E2 }
    select.grid-cell option[value="L"] { background: #FEF3C7 }
    select.grid-cell option[value="D"] { background: #DBEAFE }
    select.grid-cell option[value="H"] { background: #FFEDD5 }
    select.grid-cell option[value="W"] { background: #E2E8F0 }
    select.grid-cell option[value="F"] { background: #F3E8FF }
</style>

<script>
function fillAll(code) {
    document.querySelectorAll('.grid-cell').forEach(s => { s.value = code; colorCell(s); });
}
function fillEmpty(code) {
    document.querySelectorAll('.grid-cell').forEach(s => {
        if (s.value === '') { s.value = code; colorCell(s); }
    });
}
function fillSundays(code) {
    document.querySelectorAll('.grid-cell').forEach(s => {
        if (s.dataset.dow === 'Sun') { s.value = code; colorCell(s); }
    });
}
function autoFillRest(presentCode, weeklyOffCode) {
    // First mark Sundays as W/off (overrides empty), then mark remaining empties as Present
    document.querySelectorAll('.grid-cell').forEach(s => {
        if (s.value === '' && s.dataset.dow === 'Sun') { s.value = weeklyOffCode; colorCell(s); }
    });
    document.querySelectorAll('.grid-cell').forEach(s => {
        if (s.value === '') { s.value = presentCode; colorCell(s); }
    });
}
function applyRange() {
    const empId  = document.getElementById('rangeEmp').value;
    const from   = parseInt(document.getElementById('rangeFrom').value);
    const to     = parseInt(document.getElementById('rangeTo').value);
    const code   = document.getElementById('rangeStatus').value;
    if (isNaN(from) || isNaN(to) || from > to) { alert('Invalid range'); return; }

    document.querySelectorAll('.grid-cell').forEach(s => {
        const day = parseInt(s.dataset.day);
        if (day < from || day > to) return;
        // If empId is set, match the cell's employee; if blank, apply to all visible
        if (empId) {
            // cell name format: cell[empId][day]
            const cellEmp = s.name.match(/cell\[(\d+)\]/)[1];
            if (cellEmp !== empId) return;
        } else {
            // Skip rows hidden by search filter
            const tr = s.closest('tr');
            if (tr && tr.style.display === 'none') return;
        }
        s.value = code;
        colorCell(s);
    });
}
function colorCell(s) {
    const colors = { 'P':'#D1FAE5','A':'#FEE2E2','L':'#FEF3C7','D':'#DBEAFE','H':'#FFEDD5','W':'#E2E8F0','F':'#F3E8FF','':'#fff' };
    s.style.backgroundColor = colors[s.value] || '#fff';
}
// Color all cells on load
document.querySelectorAll('.grid-cell').forEach(colorCell);
// And on change
document.querySelectorAll('.grid-cell').forEach(s => s.addEventListener('change', () => colorCell(s)));
</script>
@endsection
