@extends('layouts.app')
@section('title', 'Payslips')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Payroll / <span class="text-slate-900 font-semibold">Payslips</span></div>

    <h1 class="text-xl font-bold mb-3">Payslips — {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h1>

    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
            <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                    <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                @endforeach
            </select></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Salary Group</label>
            <select name="group_id" class="border border-[var(--line)] rounded p-2 text-sm" style="min-width:200px">
                <option value="">All Groups</option>
                @foreach($salaryGroups as $g)
                    <option value="{{ $g->salary_group_id }}" @selected(request('group_id') == $g->salary_group_id)>{{ $g->salary_group_name }}</option>
                @endforeach
            </select></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Emp ID</label>
            <input type="number" name="emp_id" value="{{ request('emp_id') }}" placeholder="e.g. 13139" class="border border-[var(--line)] rounded p-2 text-sm" style="width:110px"/></div>
        <button type="submit" class="tb-btn primary">Filter</button>
    </form>

    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total Payslips</div><div class="text-lg font-bold">{{ $totals['count'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total Gross</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['gross'], 0) }}</div></div>
        <div class="card p-3" style="background:#F0FDF4;border-color:#86EFAC"><div class="text-[11px] text-green-700 uppercase font-semibold">Total Net Pay</div><div class="text-lg font-bold text-green-700">&#8377;{{ number_format($totals['net'], 0) }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl text-xs">
            <thead><tr>
                <th>Emp ID</th><th>Name</th><th>Department</th><th>Salary Group</th>
                <th class="text-right">Gross</th>
                <th class="text-right">Total Ded</th>
                <th class="text-right">Net Pay</th>
                <th>Status</th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
                @forelse($payslips as $p)
                    <tr>
                        <td>{{ $p->emp_id }}</td>
                        <td>{{ $p->emp->full_name ?? '—' }}</td>
                        <td>{{ $p->emp->department->dept_name ?? '—' }}</td>
                        <td>{{ $p->emp->salary_group->salary_group_name ?? '—' }}</td>
                        <td class="text-right">&#8377;{{ number_format((float)$p->gross_earnings, 0) }}</td>
                        <td class="text-right text-red-700">&#8377;{{ number_format((float)$p->total_deductions, 0) }}</td>
                        <td class="text-right text-green-700"><strong>&#8377;{{ number_format((float)$p->net_pay, 0) }}</strong></td>
                        <td>
                            <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold
                                @if($p->disbursement_status === 'Paid')      bg-green-100 text-green-800
                                @elseif($p->disbursement_status === 'Pending') bg-amber-100 text-amber-800
                                @else                                          bg-slate-100 text-slate-700 @endif">
                                {{ $p->disbursement_status ?? 'Pending' }}
                            </span>
                        </td>
                        <td class="flex gap-1">
                            <a href="{{ route('payroll.payslips.show', [$p->emp_id, $p->period_year, $p->period_month]) }}" class="tb-btn primary" style="padding:2px 8px;font-size:11px">View</a>
                            <a href="{{ route('payroll.payslips.print', [$p->emp_id, $p->period_year, $p->period_month]) }}" target="_blank" class="tb-btn" style="padding:2px 8px;font-size:11px">🖨 Print</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-6 text-slate-500">
                        No payslips for {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}.
                        <br><a href="{{ route('payroll.generate', ['year'=>$year,'month'=>$month]) }}" class="text-[var(--brand)] font-semibold">Generate salary →</a>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payslips->hasPages())<div class="mt-3">{{ $payslips->links() }}</div>@endif
</div>
@endsection
