@extends('layouts.app')
@section('title', 'Professional Tax')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">Professional Tax</span></div>
    <h1 class="text-xl font-bold mb-3">Professional Tax</h1>

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

    <div class="card p-4 mb-4 flex items-center justify-between" style="background:#FEF2F2;border-color:#FCA5A5">
        <div><div class="text-[11px] text-red-700 uppercase font-semibold">Total PT for Period</div>
            <div class="text-2xl font-bold text-red-700">&#8377;{{ number_format($total, 2) }}</div></div>
        <div class="text-xs text-slate-500">{{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }} • {{ $rows->count() }} employees</div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Employee</th><th>State</th><th>Slab Applied</th><th>PT Amount</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->employee_name ?? $r->emp_id ?? '—' }}</td>
                        <td>{{ $r->state ?? '—' }}</td>
                        <td>{{ $r->slab_applied ?? '—' }}</td>
                        <td>&#8377;{{ number_format($r->pt_amount ?? 0, 2) }}</td>
                        <td><span class="pill {{ ($r->status ?? '') === 'Paid' ? 'pill-ok' : 'pill-warn' }}">{{ $r->status ?? 'Pending' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6 text-slate-500">
                        No PT records for this period. Records are generated when payroll is run.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
