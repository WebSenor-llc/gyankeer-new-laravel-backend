@extends('layouts.app')
@section('title', 'Labour Welfare Fund')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">LWF</span></div>
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <h1 class="text-xl font-bold">Labour Welfare Fund (LWF)</h1>
        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('statutory.lwf.generate') }}" class="inline">
                @csrf
                <input type="hidden" name="year"            value="{{ $year }}">
                <input type="hidden" name="half"            value="{{ $half }}">
                <input type="hidden" name="salary_group_id" value="{{ $salaryGroupId }}">
                <button class="tb-btn primary">Generate LWF for {{ $half }} {{ $year }}</button>
            </form>
            <a href="{{ route('statutory.lwf.pdf', ['year' => $year, 'half' => $half, 'salary_group_id' => $salaryGroupId]) }}"
               target="_blank"
               class="tb-btn primary"
               style="background:#DC2626;border-color:#B91C1C">⬇ PDF</a>
            <a href="{{ route('statutory.lwf.pdf', ['year' => $year, 'half' => $half, 'salary_group_id' => $salaryGroupId, 'format' => 'csv']) }}"
               class="tb-btn primary"
               style="background:#16A34A;border-color:#15803D">⬇ CSV</a>
            <a href="{{ route('statutory.lwf.pdf', ['year' => $year, 'half' => $half, 'salary_group_id' => $salaryGroupId, 'format' => 'xls']) }}"
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
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Half</label>
            <select name="half" class="border border-[var(--line)] rounded p-2 text-sm">
                <option value="H1" @selected($half==='H1')>H1 (Jan–Jun)</option>
                <option value="H2" @selected($half==='H2')>H2 (Jul–Dec)</option>
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

    <div class="card p-4 mb-4" style="background:#FEF2F2;border-color:#FCA5A5">
        <div class="text-[11px] text-red-700 uppercase font-semibold">Total LWF for {{ $year }} {{ $half }}</div>
        <div class="text-2xl font-bold text-red-700">&#8377;{{ number_format($total, 0) }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ $rows->count() }} contributions • LWF is half-yearly in most states (Jun & Dec deposits)</div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Employee</th><th>State</th><th>EE Contribution</th><th>ER Contribution</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td class="font-mono text-xs">{{ $r->emp_id ?? '—' }}</td>
                        <td>{{ $r->employee_name ?? '—' }}</td>
                        <td>{{ $r->state ?? '—' }}</td>
                        <td>&#8377;{{ number_format($r->employee_contribution ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->employer_contribution ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->total_contribution ?? 0, 0) }}</td>
                        <td><span class="pill {{ ($r->status ?? '') === 'Paid' ? 'pill-ok' : 'pill-warn' }}">{{ $r->status ?? 'Pending' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6 text-slate-500">
                        No LWF records for {{ $year }} {{ $half }}{{ $salaryGroupId ? ' in selected group' : '' }}.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
