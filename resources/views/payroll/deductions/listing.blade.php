@extends('layouts.app')
@section('title', 'Salary Deductions')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Payroll / <span class="text-slate-900 font-semibold">Salary Deductions</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Salary Deductions &mdash; {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h1>
        <a href="{{ route('deductions.create', ['year'=>$year,'month'=>$month]) }}" class="tb-btn primary">+ Add Salary Deduction</a>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    {{-- Filter form: year/month/emp_id/name --}}
    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
            <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                    <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                @endforeach
            </select></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Emp ID</label>
            <input type="number" name="emp_id" value="{{ request('emp_id') }}" placeholder="e.g. 13002" class="border border-[var(--line)] rounded p-2 text-sm" style="width:110px"/></div>
        <div class="flex-1"><label class="block text-xs font-semibold text-slate-600 mb-1">Name search</label>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name…" class="border border-[var(--line)] rounded p-2 text-sm w-full"/></div>
        <button type="submit" class="tb-btn primary">Apply</button>
        @if(request('emp_id') || request('q'))
            <a href="{{ route('deductions.listing', ['year'=>$year,'month'=>$month]) }}" class="tb-btn">Clear</a>
        @endif
    </form>

    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total EPF</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['epf'], 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total VPF</div><div class="text-lg font-bold text-blue-700">&#8377;{{ number_format($totals['vpf'] ?? 0, 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total ESI</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['esi'], 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total PT</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['pt'], 0) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total TDS</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['tds'], 0) }}</div></div>
        <div class="card p-3" style="background:#FEF2F2;border-color:#FCA5A5"><div class="text-[11px] text-red-700 uppercase font-semibold">Grand Total</div><div class="text-lg font-bold text-red-700">&#8377;{{ number_format($totals['total'], 0) }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl text-xs">
            <thead><tr>
                <th>Emp ID</th><th>Name</th>
                <th>EPF</th>
                <th style="background:#DBEAFE">VPF</th>
                <th>ESI</th><th>PT</th><th>LWF</th>
                <th style="background:#FEF3C7;min-width:140px">TDS (editable)</th>
                <th>Loan EMI</th><th>Advance</th><th>Fine</th><th>Other</th>
                <th>Total</th>
                <th style="min-width:130px">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr class="@if(!$r['has_payslip'] && !$r['has_manual']) opacity-50 @endif">
                        <td>{{ $r['emp_id'] }}</td>
                        <td>
                            {{ $r['name'] }}
                            @if(!$r['has_payslip'] && $r['has_manual'])
                                <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold" title="Pre-entered before payroll run">PRE-ENTERED</span>
                            @endif
                        </td>
                        <td>&#8377;{{ number_format($r['epf'], 0) }}</td>
                        <td style="background:#DBEAFE">&#8377;{{ number_format($r['vpf'] ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r['esi'], 0) }}</td>
                        <td>&#8377;{{ number_format($r['pt'], 0) }}</td>
                        <td>&#8377;{{ number_format($r['lwf'], 0) }}</td>
                        {{-- Quick TDS edit form (per row) — works pre- and post-payroll --}}
                        <td style="background:#FEF3C7;padding:2px">
                            <form method="POST" action="{{ route('deductions.update-tds') }}" class="flex gap-1 items-center" onsubmit="return confirm('Update TDS for {{ $r['name'] }} to ₹' + this.tds.value + '?')">
                                @csrf
                                <input type="hidden" name="emp_id" value="{{ $r['emp_id'] }}">
                                <input type="hidden" name="period_year"  value="{{ $r['period_year'] }}">
                                <input type="hidden" name="period_month" value="{{ $r['period_month'] }}">
                                <input type="number" name="tds" step="0.01" min="0" value="{{ $r['tds'] }}"
                                       class="border border-[var(--line)] rounded p-1 text-xs text-right font-bold" style="width:90px">
                                <button type="submit" class="tb-btn" style="padding:2px 6px;font-size:10px;background:#16A34A;color:#fff;border-color:#15803D" title="Save TDS">💾</button>
                            </form>
                        </td>
                        <td>&#8377;{{ number_format($r['loan_emi'], 0) }}</td>
                        <td>&#8377;{{ number_format($r['advance'], 0) }}</td>
                        <td>&#8377;{{ number_format($r['fine'], 0) }}</td>
                        <td>&#8377;{{ number_format($r['post_ded'], 0) }}</td>
                        <td><strong>&#8377;{{ number_format($r['total'], 0) }}</strong></td>
                        <td>
                            <a href="{{ route('deductions.edit', ['empId' => $r['emp_id'], 'year' => $r['period_year'], 'month' => $r['period_month']]) }}"
                               class="tb-btn" style="padding:2px 8px;font-size:11px">Edit all</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14" class="text-center py-6 text-slate-500">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif

    <p class="text-[11px] text-slate-400 mt-2">
        Faded rows have no payslip yet — run payroll for this period to generate.
        TDS column is editable inline · click "Edit all" to open the full SUGAM-style deduction form.
    </p>
</div>
@endsection
