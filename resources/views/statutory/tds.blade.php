@extends('layouts.app')
@section('title', 'TDS Estimate')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">TDS</span></div>
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <h1 class="text-xl font-bold">Income Tax (TDS)</h1>
        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('statutory.tds.generate') }}" class="inline">
                @csrf
                <input type="hidden" name="year"            value="{{ $year ?? now()->year }}">
                <input type="hidden" name="month"           value="{{ $month ?? now()->month }}">
                <input type="hidden" name="salary_group_id" value="{{ $salaryGroupId }}">
                <button class="tb-btn primary">Generate TDS for {{ \DateTime::createFromFormat('!m', $month ?? now()->month)->format('M') }} {{ $year ?? now()->year }}</button>
            </form>
            <a href="{{ route('statutory.tds.pdf', ['year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId]) }}"
               target="_blank"
               class="tb-btn primary"
               style="background:#DC2626;border-color:#B91C1C">⬇ PDF</a>
            <a href="{{ route('statutory.tds.pdf', ['year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId, 'format' => 'csv']) }}"
               class="tb-btn primary"
               style="background:#16A34A;border-color:#15803D">⬇ CSV</a>
            <a href="{{ route('statutory.tds.pdf', ['year' => $year, 'month' => $month, 'salary_group_id' => $salaryGroupId, 'format' => 'xls']) }}"
               class="tb-btn primary"
               style="background:#0EA5E9;border-color:#0284C7">⬇ Excel</a>
        </div>
    </div>
    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
            <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                    <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                @endforeach
            </select></div>
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

    <p class="text-xs text-slate-500 mb-3">When no TDS records exist for the period, this page shows a live annual estimate from employee gross. Click "Generate TDS" to persist actual monthly TDS from payslips.</p>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    <th>Emp ID</th><th>Name</th><th>PAN</th><th>Regime</th>
                    <th>Annual Gross</th><th>Estimated Annual Tax</th><th>Monthly TDS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td class="font-mono text-xs">{{ $r['emp_id'] }}</td>
                        <td>{{ $r['name'] ?? '—' }}</td>
                        <td>{{ $r['pan'] ?? '—' }}</td>
                        <td><span class="pill pill-info">{{ $r['regime'] }}</span></td>
                        <td>&#8377;{{ number_format($r['annual_gross'], 0) }}</td>
                        <td>&#8377;{{ number_format($r['annual_tax'], 0) }}</td>
                        <td>&#8377;{{ number_format($r['monthly_tds'], 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6 text-slate-500">
                        No active employees{{ $salaryGroupId ? ' in selected group' : '' }} yet. Add employees from <a href="/employees/create" class="text-[var(--brand)] font-semibold">Manage Employee</a>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
