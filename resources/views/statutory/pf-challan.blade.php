@extends('layouts.app')
@section('title', 'PF Challan')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">PF Challan / ECR</span></div>

    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <h1 class="text-xl font-bold">PF Challan / ECR</h1>
        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('statutory.pf.generate') }}" class="inline">
                @csrf
                <input type="hidden" name="year"            value="{{ $year }}">
                <input type="hidden" name="month"           value="{{ $month }}">
                <input type="hidden" name="salary_group_id" value="{{ $salaryGroupId }}">
                <button class="tb-btn primary">Generate ECR for this period</button>
            </form>
            <a href="{{ route('statutory.pf.pdf', ['year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId]) }}"
               target="_blank"
               class="tb-btn primary"
               style="background:#DC2626;border-color:#B91C1C">⬇ PDF</a>
            <a href="{{ route('statutory.pf.pdf', ['year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId, 'format' => 'csv']) }}"
               class="tb-btn primary"
               style="background:#16A34A;border-color:#15803D">⬇ CSV (ECR Upload)</a>
            <a href="{{ route('statutory.pf.pdf', ['year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId, 'format' => 'xls']) }}"
               class="tb-btn primary"
               style="background:#0EA5E9;border-color:#0284C7">⬇ Excel</a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-blue-50 border border-blue-200 px-3 py-2 text-sm text-blue-800">{{ session('status') }}</div>
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

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">EE Share (12%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['ee'], 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">EPS (8.33%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['eps'], 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">ER Share (3.67%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['er'], 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">EDLI (0.5%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['edli'], 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Admin (0.5%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['admin'], 0) }}</div></div>
        <div class="card p-3" style="background:#FEF2F2;border-color:#FCA5A5"><div class="text-[11px] text-red-700 uppercase font-semibold">Total Challan</div><div class="text-lg font-bold text-red-700">&#8377;{{ number_format($totals['challan'], 0) }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Member</th>
                    <th>UAN</th>
                    <th>Member ID</th>
                    <th>Gross Wage</th>
                    <th>EPF Wage (capped)</th>
                    <th>EE 12%</th>
                    <th>EPS 8.33%</th>
                    <th>ER 3.67%</th>
                    <th>EDLI 0.5%</th>
                    <th>Admin 0.5%</th>
                    <th>NCP</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td class="font-mono text-xs">{{ $r->emp_id }}</td>
                        <td>{{ $r->member_name ?? '—' }}</td>
                        <td>{{ $r->uan ?? '—' }}</td>
                        <td>{{ $r->member_id_pf ?? '—' }}</td>
                        <td>&#8377;{{ number_format($r->gross_wage ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->epf_wage_capped ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->ee_share_12pct ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->eps_8_33 ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->er_share_3_67 ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->edli_0_5 ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->pf_admin_0_5 ?? 0, 0) }}</td>
                        <td>{{ rtrim(rtrim(number_format((float) ($r->ncp_days ?? 0), 2, '.', ''), '0'), '.') ?: '0' }}</td>
                        <td><span class="pill {{ ($r->filed_status ?? '') === 'Filed' ? 'pill-ok' : 'pill-warn' }}">{{ $r->filed_status ?? 'Pending' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="text-center py-6 text-slate-500">
                        No PF ECR records for {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}{{ $salaryGroupId ? ' in selected group' : '' }}.
                        <br><span class="text-xs">Run payroll for this period first, then click "Generate ECR" above.</span>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
