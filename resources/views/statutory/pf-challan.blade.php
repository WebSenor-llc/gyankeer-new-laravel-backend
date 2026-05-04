@extends('layouts.app')
@section('title', 'PF Challan')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">PF Challan / ECR</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">PF Challan / ECR</h1>
        <form method="POST" action="{{ route('statutory.pf.generate') }}" class="inline">
            @csrf
            <input type="hidden" name="year"  value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <button class="tb-btn primary">Generate ECR for this period</button>
        </form>
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
        <button type="submit" class="tb-btn primary">Apply</button>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">EE Share (12%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['ee'], 2) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">EPS (8.33%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['eps'], 2) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">ER Share (3.67%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['er'], 2) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">EDLI (0.5%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['edli'], 2) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Admin (0.5%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['admin'], 2) }}</div></div>
        <div class="card p-3" style="background:#FEF2F2;border-color:#FCA5A5"><div class="text-[11px] text-red-700 uppercase font-semibold">Total Challan</div><div class="text-lg font-bold text-red-700">&#8377;{{ number_format($totals['challan'], 2) }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    <th>UAN</th>
                    <th>Member</th>
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
                        <td>{{ $r->uan ?? '—' }}</td>
                        <td>{{ $r->member_name ?? '—' }}</td>
                        <td>{{ $r->member_id_pf ?? '—' }}</td>
                        <td>&#8377;{{ number_format($r->gross_wage ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->epf_wage_capped ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->ee_share_12pct ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->eps_8_33 ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->er_share_3_67 ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->edli_0_5 ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->pf_admin_0_5 ?? 0, 2) }}</td>
                        <td>{{ $r->ncp_days ?? 0 }}</td>
                        <td><span class="pill {{ ($r->filed_status ?? '') === 'Filed' ? 'pill-ok' : 'pill-warn' }}">{{ $r->filed_status ?? 'Pending' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center py-6 text-slate-500">
                        No PF ECR records for {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}.
                        <br><span class="text-xs">Run payroll for this period first, then click "Generate ECR" above.</span>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
