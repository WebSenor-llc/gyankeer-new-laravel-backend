@extends('layouts.app')
@section('title', 'Attendance — Summary Entry')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Attendance & Leave / <span class="text-slate-900 font-semibold">Summary Entry</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Summary Entry — {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('attendance.grid', ['year'=>$year,'month'=>$month]) }}" class="tb-btn">Day-by-day Grid</a>
            <a href="{{ route('attendance.daily') }}" class="tb-btn">Daily View</a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    {{-- Period filter --}}
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
        <button type="submit" class="tb-btn primary">Apply Filter</button>
    </form>

    {{-- How it works box --}}
    <div class="card p-3 mb-3 text-xs" style="background:#FEF3C7;border-color:#FCD34D">
        <strong>How it works:</strong>
        Per employee, enter the count of <strong>Present</strong> days (P), the count of <strong>Weekly Off</strong> (W),
        and (optionally) the date range for <strong>Leave</strong> (L) and <strong>Absent</strong> (A).<br>
        On save, the system distributes:
        <ul class="list-disc list-inside mt-1">
            <li>W → Sundays first ({{ $sundays }} Sundays in {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}), then Saturdays if more needed</li>
            <li>L → the date range you enter</li>
            <li>A → the date range you enter</li>
            <li>P → all remaining days</li>
        </ul>
        Total must equal {{ $totalDays }} days. Validation checks the count.
    </div>

    {{-- Quick fill toolbar --}}
    <div class="card p-3 mb-3 flex flex-wrap gap-2 items-center text-xs">
        <span class="font-semibold">Quick fill all rows:</span>
        <button type="button" onclick="fillRowsPattern({{ $totalDays - $sundays }}, {{ $sundays }}, 0)" class="tb-btn">
            Default ({{ $totalDays - $sundays }}P + {{ $sundays }}W)
        </button>
        <button type="button" onclick="fillRowsPattern(25, 4, 0)" class="tb-btn">25P + 4W (no leave)</button>
        <button type="button" onclick="fillRowsPattern({{ $totalDays - $sundays - 1 }}, {{ $sundays }}, 1)" class="tb-btn">
            {{ $totalDays - $sundays - 1 }}P + {{ $sundays }}W + 1L
        </button>
        <button type="button" onclick="clearRows()" class="tb-btn">Clear All</button>
    </div>

    <form method="POST" action="{{ route('attendance.summary.save') }}" id="summaryForm">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">

        <div class="card overflow-x-auto">
            <table class="grid-tbl text-xs" id="summaryTable">
                <thead>
                    <tr style="background:#F1F5F9">
                        <th rowspan="2">Emp ID</th>
                        <th rowspan="2">Name</th>
                        <th rowspan="2">Dept</th>
                        <th rowspan="2" style="background:#D1FAE5">P<br><span class="font-normal">(Present)</span></th>
                        <th rowspan="2" style="background:#E2E8F0">W<br><span class="font-normal">(W/Off)</span></th>
                        <th colspan="2" style="background:#FEF3C7">L (On Leave) — date range</th>
                        <th colspan="2" style="background:#FEE2E2">A (Absent) — date range</th>
                        <th rowspan="2" style="min-width:60px">Total<br><span class="font-normal">({{ $totalDays }})</span></th>
                    </tr>
                    <tr style="background:#F1F5F9">
                        <th style="background:#FEF3C7">From</th>
                        <th style="background:#FEF3C7">To</th>
                        <th style="background:#FEE2E2">From</th>
                        <th style="background:#FEE2E2">To</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $e)
                        @php
                            $existingCounts = $existing[$e->emp_id] ?? null;
                            $defP = $existingCounts ? $existingCounts['Present'] : 0;
                            $defW = $existingCounts ? $existingCounts['Weekly Off'] : 0;
                        @endphp
                        <tr data-empid="{{ $e->emp_id }}">
                            <td>{{ $e->emp_id }}</td>
                            <td style="white-space:nowrap;max-width:180px;overflow:hidden;text-overflow:ellipsis">{{ $e->full_name }}</td>
                            <td style="font-size:10px;color:#64748B">{{ $e->department->dept_name ?? '—' }}</td>
                            <td style="background:#D1FAE5">
                                <input type="number" min="0" max="{{ $totalDays }}" name="row[{{ $e->emp_id }}][p]"
                                       value="{{ $defP > 0 ? $defP : '' }}"
                                       class="row-p w-full border border-[var(--line)] rounded p-1 text-xs text-center font-bold" oninput="recalcTotal(this)">
                            </td>
                            <td style="background:#E2E8F0">
                                <input type="number" min="0" max="{{ $totalDays }}" name="row[{{ $e->emp_id }}][w]"
                                       value="{{ $defW > 0 ? $defW : '' }}"
                                       class="row-w w-full border border-[var(--line)] rounded p-1 text-xs text-center font-bold" oninput="recalcTotal(this)">
                            </td>
                            <td style="background:#FEF3C7">
                                <input type="date" name="row[{{ $e->emp_id }}][l_from]" min="{{ $year }}-{{ str_pad($month,2,'0',STR_PAD_LEFT) }}-01" max="{{ $year }}-{{ str_pad($month,2,'0',STR_PAD_LEFT) }}-{{ str_pad($totalDays,2,'0',STR_PAD_LEFT) }}"
                                       class="row-lfrom border border-[var(--line)] rounded p-1 text-xs" oninput="recalcTotal(this)">
                            </td>
                            <td style="background:#FEF3C7">
                                <input type="date" name="row[{{ $e->emp_id }}][l_to]" min="{{ $year }}-{{ str_pad($month,2,'0',STR_PAD_LEFT) }}-01" max="{{ $year }}-{{ str_pad($month,2,'0',STR_PAD_LEFT) }}-{{ str_pad($totalDays,2,'0',STR_PAD_LEFT) }}"
                                       class="row-lto border border-[var(--line)] rounded p-1 text-xs" oninput="recalcTotal(this)">
                            </td>
                            <td style="background:#FEE2E2">
                                <input type="date" name="row[{{ $e->emp_id }}][a_from]" min="{{ $year }}-{{ str_pad($month,2,'0',STR_PAD_LEFT) }}-01" max="{{ $year }}-{{ str_pad($month,2,'0',STR_PAD_LEFT) }}-{{ str_pad($totalDays,2,'0',STR_PAD_LEFT) }}"
                                       class="row-afrom border border-[var(--line)] rounded p-1 text-xs" oninput="recalcTotal(this)">
                            </td>
                            <td style="background:#FEE2E2">
                                <input type="date" name="row[{{ $e->emp_id }}][a_to]" min="{{ $year }}-{{ str_pad($month,2,'0',STR_PAD_LEFT) }}-01" max="{{ $year }}-{{ str_pad($month,2,'0',STR_PAD_LEFT) }}-{{ str_pad($totalDays,2,'0',STR_PAD_LEFT) }}"
                                       class="row-ato border border-[var(--line)] rounded p-1 text-xs" oninput="recalcTotal(this)">
                            </td>
                            <td class="row-total text-center font-bold">{{ $totalDays }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-6 text-slate-500">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif

        <div class="sticky bottom-0 z-10 bg-white border-t border-[var(--line)] py-3 px-4 mt-3 flex justify-between items-center">
            <div class="text-xs text-slate-500">
                Tip: Click "Default ({{ $totalDays - $sundays }}P + {{ $sundays }}W)" then add specific leaves per row.
            </div>
            <button type="submit" class="tb-btn primary" style="font-size:14px;padding:10px 20px">💾 Save Attendance Summary</button>
        </div>
    </form>
</div>

<script>
const TOTAL_DAYS = {{ $totalDays }};

function dayDiff(from, to) {
    if (!from || !to) return 0;
    const f = new Date(from), t = new Date(to);
    if (t < f) return 0;
    return Math.floor((t - f) / 86400000) + 1;
}

function recalcTotal(input) {
    const tr = input.closest('tr');
    if (!tr) return;
    const p  = parseInt(tr.querySelector('.row-p').value)  || 0;
    const w  = parseInt(tr.querySelector('.row-w').value)  || 0;
    const lf = tr.querySelector('.row-lfrom').value;
    const lt = tr.querySelector('.row-lto').value;
    const af = tr.querySelector('.row-afrom').value;
    const at = tr.querySelector('.row-ato').value;
    const lDays = dayDiff(lf, lt);
    const aDays = dayDiff(af, at);
    const total = p + w + lDays + aDays;
    const cell = tr.querySelector('.row-total');
    cell.textContent = total;
    if (total === TOTAL_DAYS)        cell.style.color = '#15803D';
    else if (total === 0)             cell.style.color = '#94A3B8';
    else if (total > TOTAL_DAYS)      cell.style.color = '#B91C1C';
    else                              cell.style.color = '#A16207';
}

function fillRowsPattern(p, w, lCount) {
    document.querySelectorAll('#summaryTable tbody tr').forEach(tr => {
        if (tr.style.display === 'none') return;
        tr.querySelector('.row-p').value = p;
        tr.querySelector('.row-w').value = w;
        // Don't auto-fill leave dates — user picks per row
        if (lCount === 0) {
            tr.querySelector('.row-lfrom').value = '';
            tr.querySelector('.row-lto').value = '';
        }
        tr.querySelector('.row-afrom').value = '';
        tr.querySelector('.row-ato').value = '';
        recalcTotal(tr.querySelector('.row-p'));
    });
}

function clearRows() {
    document.querySelectorAll('#summaryTable tbody tr').forEach(tr => {
        tr.querySelectorAll('input').forEach(i => { i.value = ''; });
        recalcTotal(tr.querySelector('.row-p'));
    });
}

// Initial total recalculation
document.querySelectorAll('#summaryTable tbody tr').forEach(tr => {
    const p = tr.querySelector('.row-p');
    if (p) recalcTotal(p);
});
</script>
@endsection
