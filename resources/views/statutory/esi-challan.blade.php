@extends('layouts.app')
@section('title', 'ESI Challan')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">ESI Challan</span></div>
    <h1 class="text-xl font-bold mb-3">ESI Challan</h1>

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

    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Employee Share (0.75%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['ee'], 2) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Employer Share (3.25%)</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['er'], 2) }}</div></div>
        <div class="card p-3" style="background:#FEF2F2;border-color:#FCA5A5"><div class="text-[11px] text-red-700 uppercase font-semibold">Total Contribution</div><div class="text-lg font-bold text-red-700">&#8377;{{ number_format($totals['total'], 2) }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    <th>IP No.</th><th>Member Name</th><th>Gross Wage</th>
                    <th>EE 0.75%</th><th>ER 3.25%</th><th>Total</th><th>Days Worked</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->ip_no ?? '—' }}</td>
                        <td>{{ $r->member_name ?? '—' }}</td>
                        <td>&#8377;{{ number_format($r->gross_wage ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->ee_0_75 ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->er_3_25 ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->total_contribution ?? 0, 2) }}</td>
                        <td>{{ $r->days_worked ?? 0 }}</td>
                        <td><span class="pill pill-warn">Pending</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-6 text-slate-500">
                        No ESI records for {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}.
                        <br><span class="text-xs">Records are generated when payroll is run for the period.</span>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
