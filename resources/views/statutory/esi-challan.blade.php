@extends('layouts.app')
@section('title', 'ESI Challan')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">ESI Challan</span></div>
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <h1 class="text-xl font-bold">ESI Challan</h1>
        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('statutory.esi.generate') }}" class="inline">
                @csrf
                <input type="hidden" name="year"            value="{{ $year }}">
                <input type="hidden" name="month"           value="{{ $month }}">
                <input type="hidden" name="salary_group_id" value="{{ $salaryGroupId }}">
                <button class="tb-btn primary">Generate ESI for this period</button>
            </form>
            <a href="{{ route('statutory.esi.pdf', ['year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId]) }}"
               target="_blank"
               class="tb-btn primary"
               style="background:#DC2626;border-color:#B91C1C">⬇ PDF</a>
            <a href="{{ route('statutory.esi.pdf', ['year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId, 'format' => 'csv']) }}"
               class="tb-btn primary"
               style="background:#16A34A;border-color:#15803D">⬇ CSV (ESIC Upload)</a>
            <a href="{{ route('statutory.esi.pdf', ['year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId, 'format' => 'xls']) }}"
               class="tb-btn primary"
               style="background:#0EA5E9;border-color:#0284C7">⬇ Excel</a>
        </div>
    </div>
    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
            <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                    <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
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

    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Employee Share (0.75%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['ee'], 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Employer Share (3.25%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['er'], 0) }}</div></div>
        <div class="card p-3" style="background:#FEF2F2;border-color:#FCA5A5"><div class="text-[11px] text-red-700 uppercase font-semibold">Total Contribution</div><div class="text-lg font-bold text-red-700">&#8377;{{ number_format($totals['total'], 0) }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    <th>Emp ID</th><th>Member Name</th><th>IP No.</th><th>Gross Wage</th>
                    <th>EE 0.75%</th><th>ER 3.25%</th><th>Total</th><th>Days Worked</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td class="font-mono text-xs">{{ $r->emp_id }}</td>
                        <td>{{ $r->member_name ?? '—' }}</td>
                        <td>{{ $r->ip_no ?? '—' }}</td>
                        <td>&#8377;{{ number_format($r->gross_wage ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->ee_0_75 ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->er_3_25 ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->total_contribution ?? 0, 0) }}</td>
                        <td>{{ $r->days_worked ?? 0 }}</td>
                        <td><span class="pill pill-warn">Pending</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-6 text-slate-500">
                        No ESI records for {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}{{ $salaryGroupId ? ' in selected group' : '' }}.
                        <br><span class="text-xs">Records are generated when payroll is run for the period.</span>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
