@extends('layouts.app')
@section('title', 'Salary Transactions')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Payroll / <span class="text-slate-900 font-semibold">Salary Transactions</span></div>
    <h1 class="text-xl font-bold mb-3">Salary Transactions (GL-style ledger)</h1>

    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
            <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                    <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                @endforeach
            </select></div>
        <div class="flex-1"><label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Employee, component, txn type..." class="border border-[var(--line)] rounded p-2 text-sm w-full"/></div>
        <button type="submit" class="tb-btn primary">Filter</button>
    </form>

    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Transactions</div><div class="text-lg font-bold">{{ $totals['count'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total Debit</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['debit'], 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total Credit</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['credit'], 0) }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr><th>Date</th><th>Employee</th><th>Type</th><th>Component</th><th>Debit</th><th>Credit</th><th>GL Account</th></tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->txn_date }}</td>
                        <td>{{ $r->employee_name ?? ('Emp #'.$r->emp_id) }}</td>
                        <td><span class="pill pill-info">{{ $r->txn_type ?? '—' }}</span></td>
                        <td>{{ $r->component_name ?? $r->component_code ?? '—' }}</td>
                        <td>&#8377;{{ number_format($r->debit_amount, 0) }}</td>
                        <td>&#8377;{{ number_format($r->credit_amount, 0) }}</td>
                        <td class="text-xs text-slate-500">{{ $r->gl_account_code }} — {{ $r->gl_account_name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6 text-slate-500">
                        No transactions for {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}.
                        <br><span class="text-xs">Transactions are auto-generated when a salary run is posted to GL.</span>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div>
@endsection
