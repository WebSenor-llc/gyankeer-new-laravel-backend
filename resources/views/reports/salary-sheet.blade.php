@extends('layouts.app')
@section('title', 'Salary Sheet')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">Salary Sheet</span></div>
    <h1 class="text-xl font-bold mb-3">Salary Sheet</h1>
    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
    <div><label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
        <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
    <div><label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
        <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
            @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
            @endforeach
        </select></div>
    <button type="submit" class="tb-btn primary">Apply</button>
</form>

    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Employees</div><div class="text-lg font-bold">{{ $totals['count'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total Gross</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['gross'], 2) }}</div></div>
        <div class="card p-3" style="background:#FEF2F2;border-color:#FCA5A5"><div class="text-[11px] text-red-700 uppercase font-semibold">Total Net Pay</div><div class="text-lg font-bold text-red-700">&#8377;{{ number_format($totals['net'], 2) }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Basic</th><th>HRA</th><th>DA</th><th>Spl</th><th>Bonus</th><th>Gross</th><th>EPF</th><th>ESI</th><th>PT</th><th>TDS</th><th>Net Pay</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->emp_id }}</td>
                        <td>&#8377;{{ number_format($r->basic, 2) }}</td>
                        <td>&#8377;{{ number_format($r->hra, 2) }}</td>
                        <td>&#8377;{{ number_format($r->da, 2) }}</td>
                        <td>&#8377;{{ number_format($r->spl_allow, 2) }}</td>
                        <td>&#8377;{{ number_format($r->bonus, 2) }}</td>
                        <td>&#8377;{{ number_format($r->gross_earnings, 2) }}</td>
                        <td>&#8377;{{ number_format($r->epf_emp ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->esi_emp ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->pt ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->tds ?? 0, 2) }}</td>
                        <td><strong>&#8377;{{ number_format($r->net_pay ?? 0, 2) }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center py-6 text-slate-500">No payslips for {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div>
@endsection
