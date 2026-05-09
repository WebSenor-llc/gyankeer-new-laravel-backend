@extends('layouts.app')
@section('title', 'Bank Sheet')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">Bank Sheet</span></div>
    <h1 class="text-xl font-bold mb-3">Bank Disbursement Sheet</h1>
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
    <div class="card p-4 mb-4" style="background:#FEF2F2;border-color:#FCA5A5">
        <div class="text-[11px] text-red-700 uppercase font-semibold">Total Disbursement</div>
        <div class="text-2xl font-bold text-red-700">&#8377;{{ number_format($total, 0) }}</div>
    </div>
    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Account No.</th><th>IFSC</th><th>Bank</th><th>Amount</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->emp_id }}</td>
                        <td>{{ $r->emp->bank_account_no ?? '—' }}</td>
                        <td>{{ $r->emp->bank_ifsc ?? '—' }}</td>
                        <td>{{ $r->emp->bank->bank_name ?? '—' }}</td>
                        <td><strong>&#8377;{{ number_format($r->net_pay ?? 0, 0) }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6 text-slate-500">No disbursements for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div>
@endsection
