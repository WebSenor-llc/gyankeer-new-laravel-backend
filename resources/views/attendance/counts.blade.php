@extends('layouts.app')
@section('title', ($workersOnly ?? false) ? 'Workers Attendance — by Contractor' : 'Attendance — Quick Counts')

@section('content')
@php $workersOnly = $workersOnly ?? false; $contractors = $contractors ?? collect(); @endphp
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">
        Attendance & Leave /
        <span class="text-slate-900 font-semibold">
            @if($workersOnly) Workers Attendance (by Contractor)
            @else Quick Counts (SUGAM-style) @endif
        </span>
    </div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">
            @if($workersOnly)
                👷 Workers Attendance — {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
            @else
                Quick Counts — {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
            @endif
        </h1>
        <div class="flex gap-2">
            @if($workersOnly)
                <a href="{{ route('attendance.counts', ['year'=>$year,'month'=>$month]) }}" class="tb-btn">All Employees View</a>
            @else
                <a href="{{ route('attendance.counts-workers', ['year'=>$year,'month'=>$month]) }}" class="tb-btn primary" style="background:#0EA5E9;border-color:#0284C7">👷 Workers Only (by Contractor)</a>
            @endif
            <a href="{{ route('attendance.summary', ['year'=>$year,'month'=>$month]) }}" class="tb-btn">Summary (P/W/L by date)</a>
            <a href="{{ route('attendance.grid',    ['year'=>$year,'month'=>$month]) }}" class="tb-btn">Day-by-day Grid</a>
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

        @if($workersOnly)
            {{-- Contractor (Salary Group) — workers only --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Contractor</label>
                <select name="salary_group_id" class="border border-[var(--line)] rounded p-2 text-sm" style="min-width:240px">
                    <option value="">— All Contractors —</option>
                    @foreach($contractors as $c)
                        <option value="{{ $c->salary_group_id }}" @selected(request('salary_group_id') == $c->salary_group_id)>{{ $c->salary_group_name }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Department</label>
                <select name="dept_id" class="border border-[var(--line)] rounded p-2 text-sm" style="min-width:160px">
                    <option value="">All</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->dept_id }}" @selected(request('dept_id') == $d->dept_id)>{{ $d->dept_name }}</option>
                    @endforeach
                </select></div>
        @endif

        <div class="flex-1"><label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Emp ID or name…" class="border border-[var(--line)] rounded p-2 text-sm w-full"/></div>
        <button type="submit" class="tb-btn primary">Apply</button>
        @if($workersOnly && (request('salary_group_id') || request('q')))
            <a href="{{ route('attendance.counts-workers', ['year'=>$year,'month'=>$month]) }}" class="tb-btn">Clear</a>
        @endif
    </form>

    {{-- Header info card --}}
    <div class="card p-3 mb-3 text-xs grid grid-cols-2 md:grid-cols-5 gap-3">
        <div><div class="text-[10px] text-slate-500 uppercase">Total Days</div><div class="text-lg font-bold">{{ $totalDays }}</div></div>
        <div><div class="text-[10px] text-slate-500 uppercase">Sundays</div><div class="text-lg font-bold">{{ $sundays }}</div></div>
        <div><div class="text-[10px] text-slate-500 uppercase">Saturdays</div><div class="text-lg font-bold">{{ $saturdays }}</div></div>
        <div><div class="text-[10px] text-slate-500 uppercase">Default P (Present)</div><div class="text-lg font-bold text-emerald-600">{{ $totalDays - $sundays }}</div></div>
        <div><div class="text-[10px] text-slate-500 uppercase">Default W (W/Off)</div><div class="text-lg font-bold text-slate-500">{{ $sundays }}</div></div>
    </div>

    {{-- Quick fill toolbar --}}
    <div class="card p-3 mb-3">
        <div class="font-semibold text-sm mb-2">Quick Fill — applies to all visible employees</div>
        <div class="flex flex-wrap gap-2 text-xs">
            <button type="button" onclick="fillAll({{ $totalDays - $sundays }}, {{ $sundays }}, 0, 0, 0, 0, 0, 0)" class="tb-btn primary">
                ⚡ Default ({{ $totalDays - $sundays }}P + {{ $sundays }}W)
            </button>
            <button type="button" onclick="fillAll(25, 4, 0, 0, 0, 1, 0, 0)" class="tb-btn">25P + 4W + 1A</button>
            <button type="button" onclick="fillAll({{ $totalDays - $sundays - 1 }}, {{ $sundays }}, 1, 0, 0, 0, 0, 0)" class="tb-btn">
                {{ $totalDays - $sundays - 1 }}P + {{ $sundays }}W + 1CL
            </button>
            <button type="button" onclick="fillAll({{ $totalDays - $sundays }}, {{ $sundays }}, 0, 0, 0, 0, 0, 20)" class="tb-btn">
                Default + 20 OT hrs
            </button>
            <button type="button" onclick="clearAll()" class="tb-btn">Clear All</button>
        </div>
        <div class="mt-2 text-[11px] text-slate-500">
            Sum of P + W + CL + SL + PL + A + HD <strong>must equal {{ $totalDays }}</strong>. Rows with mismatched totals are skipped.
        </div>
    </div>

    <form method="POST" action="{{ route('attendance.counts.save') }}" id="countsForm">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">

        <div class="card overflow-x-auto">
            <table class="grid-tbl text-xs" id="countsTable" style="font-size:11px">
                <thead>
                    <tr style="background:#F1F5F9">
                        <th style="position:sticky;left:0;background:#F1F5F9;z-index:2;min-width:60px">Emp ID</th>
                        <th style="position:sticky;left:60px;background:#F1F5F9;z-index:2;min-width:170px">Name</th>
                        <th style="min-width:140px">{{ $workersOnly ? 'Contractor' : 'Dept' }}</th>
                        <th style="background:#D1FAE5;min-width:50px">P</th>
                        <th style="background:#E2E8F0;min-width:50px">W</th>
                        <th style="background:#FEF3C7;min-width:50px">CL</th>
                        <th style="background:#FEF3C7;min-width:50px">SL</th>
                        <th style="background:#FEF3C7;min-width:50px">PL</th>
                        <th style="background:#FEE2E2;min-width:50px">A</th>
                        <th style="background:#FFEDD5;min-width:50px">HD</th>
                        <th style="background:#DBEAFE;min-width:60px">OT (h)</th>
                        <th style="min-width:60px;background:#F8FAFC">Total<br><span class="font-normal text-[10px]">(={{ $totalDays }})</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $e)
                        @php $c = $existing[$e->emp_id] ?? ['p'=>0,'w'=>0,'cl'=>0,'sl'=>0,'pl'=>0,'a'=>0,'hd'=>0,'ot'=>0]; @endphp
                        <tr data-empid="{{ $e->emp_id }}">
                            <td style="position:sticky;left:0;background:#fff;z-index:1;font-weight:600">{{ $e->emp_id }}</td>
                            <td style="position:sticky;left:60px;background:#fff;z-index:1;white-space:nowrap;max-width:170px;overflow:hidden;text-overflow:ellipsis">{{ $e->full_name }}</td>
                            <td style="font-size:10px;color:#64748B">
                                @if($workersOnly)
                                    {{ $e->salary_group->salary_group_name ?? '—' }}
                                @else
                                    {{ $e->department->dept_name ?? '—' }}
                                @endif
                            </td>
                            <td style="background:#D1FAE5;padding:1px"><input type="number" min="0" max="{{ $totalDays }}" step="0.5" name="row[{{ $e->emp_id }}][p]"  value="{{ $c['p']  ?: '' }}" class="cnt cnt-p  w-full border border-[var(--line)] rounded p-1 text-xs text-center font-bold" oninput="recalcRow(this)"></td>
                            <td style="background:#E2E8F0;padding:1px"><input type="number" min="0" max="{{ $totalDays }}" step="0.5" name="row[{{ $e->emp_id }}][w]"  value="{{ $c['w']  ?: '' }}" class="cnt cnt-w  w-full border border-[var(--line)] rounded p-1 text-xs text-center" oninput="recalcRow(this)"></td>
                            <td style="background:#FEF3C7;padding:1px"><input type="number" min="0" max="{{ $totalDays }}" step="0.5" name="row[{{ $e->emp_id }}][cl]" value="{{ $c['cl'] ?: '' }}" class="cnt cnt-cl w-full border border-[var(--line)] rounded p-1 text-xs text-center" oninput="recalcRow(this)"></td>
                            <td style="background:#FEF3C7;padding:1px"><input type="number" min="0" max="{{ $totalDays }}" step="0.5" name="row[{{ $e->emp_id }}][sl]" value="{{ $c['sl'] ?: '' }}" class="cnt cnt-sl w-full border border-[var(--line)] rounded p-1 text-xs text-center" oninput="recalcRow(this)"></td>
                            <td style="background:#FEF3C7;padding:1px"><input type="number" min="0" max="{{ $totalDays }}" step="0.5" name="row[{{ $e->emp_id }}][pl]" value="{{ $c['pl'] ?: '' }}" class="cnt cnt-pl w-full border border-[var(--line)] rounded p-1 text-xs text-center" oninput="recalcRow(this)"></td>
                            <td style="background:#FEE2E2;padding:1px"><input type="number" min="0" max="{{ $totalDays }}" step="0.5" name="row[{{ $e->emp_id }}][a]"  value="{{ $c['a']  ?: '' }}" class="cnt cnt-a  w-full border border-[var(--line)] rounded p-1 text-xs text-center" oninput="recalcRow(this)"></td>
                            <td style="background:#FFEDD5;padding:1px"><input type="number" min="0" max="{{ $totalDays }}" step="0.5" name="row[{{ $e->emp_id }}][hd]" value="{{ $c['hd'] ?: '' }}" class="cnt cnt-hd w-full border border-[var(--line)] rounded p-1 text-xs text-center" oninput="recalcRow(this)"></td>
                            <td style="background:#DBEAFE;padding:1px"><input type="number" min="0" max="744" step="0.5" name="row[{{ $e->emp_id }}][ot]" value="{{ $c['ot'] ?: '' }}" class="cnt-ot w-full border border-[var(--line)] rounded p-1 text-xs text-center"></td>
                            <td class="row-total text-center font-bold" style="background:#F8FAFC">0</td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center py-6 text-slate-500">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif

        <div class="sticky bottom-0 z-10 bg-white border-t border-[var(--line)] py-3 px-4 mt-3 flex justify-between items-center">
            <div class="text-xs text-slate-500">
                <span id="okCount" class="text-green-600 font-bold">0</span> ready ·
                <span id="badCount" class="text-amber-600 font-bold">0</span> mismatched ·
                <span id="emptyCount" class="text-slate-500">0</span> empty
            </div>
            <button type="submit" class="tb-btn primary" style="font-size:14px;padding:10px 20px">💾 Save Counts</button>
        </div>
    </form>
</div>

<script>
const TOTAL_DAYS = {{ $totalDays }};

function recalcRow(input) {
    const tr = input.closest('tr');
    if (!tr) return;
    let sum = 0;
    tr.querySelectorAll('input.cnt').forEach(i => { sum += (parseFloat(i.value) || 0); });
    sum = Math.round(sum * 100) / 100;  // avoid 30.000000001 from float math
    const cell = tr.querySelector('.row-total');
    cell.textContent = sum;
    if (sum === 0) {
        cell.style.color = '#94A3B8';
        cell.style.background = '#F8FAFC';
    } else if (Math.abs(sum - TOTAL_DAYS) < 0.001) {
        cell.style.color = '#15803D';
        cell.style.background = '#D1FAE5';
    } else {
        cell.style.color = '#B91C1C';
        cell.style.background = '#FEE2E2';
    }
    refreshCounters();
}

function refreshCounters() {
    let ok=0, bad=0, empty=0;
    document.querySelectorAll('#countsTable tbody tr').forEach(tr => {
        let sum = 0;
        tr.querySelectorAll('input.cnt').forEach(i => { sum += (parseFloat(i.value) || 0); });
        if (sum === 0) empty++;
        else if (Math.abs(sum - TOTAL_DAYS) < 0.001) ok++;
        else bad++;
    });
    document.getElementById('okCount').textContent    = ok;
    document.getElementById('badCount').textContent   = bad;
    document.getElementById('emptyCount').textContent = empty;
}

function fillAll(p, w, cl, sl, pl, a, hd, ot) {
    document.querySelectorAll('#countsTable tbody tr').forEach(tr => {
        if (tr.style.display === 'none') return;
        const fields = {p, w, cl, sl, pl, a, hd};
        Object.entries(fields).forEach(([k, v]) => {
            const input = tr.querySelector('.cnt-' + k);
            if (input) input.value = v || '';
        });
        const otInput = tr.querySelector('.cnt-ot');
        if (otInput && ot > 0) otInput.value = ot;
        recalcRow(tr.querySelector('.cnt-p'));
    });
}

function clearAll() {
    document.querySelectorAll('#countsTable tbody tr').forEach(tr => {
        tr.querySelectorAll('input').forEach(i => { i.value = ''; });
        recalcRow(tr.querySelector('.cnt-p'));
    });
}

// Initial calc on load
document.querySelectorAll('#countsTable tbody tr').forEach(tr => {
    const p = tr.querySelector('.cnt-p');
    if (p) recalcRow(p);
});
</script>
@endsection
