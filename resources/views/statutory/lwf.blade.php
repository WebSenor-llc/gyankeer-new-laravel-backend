@extends('layouts.app')
@section('title', 'Labour Welfare Fund')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">LWF</span></div>
    <h1 class="text-xl font-bold mb-3">Labour Welfare Fund (LWF)</h1>

    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Half</label>
            <select name="half" class="border border-[var(--line)] rounded p-2 text-sm">
                <option value="H1" @selected($half==='H1')>H1 (Jan–Jun)</option>
                <option value="H2" @selected($half==='H2')>H2 (Jul–Dec)</option>
            </select></div>
        <button type="submit" class="tb-btn primary">Apply</button>
    </form>

    <div class="card p-4 mb-4" style="background:#FEF2F2;border-color:#FCA5A5">
        <div class="text-[11px] text-red-700 uppercase font-semibold">Total LWF for {{ $year }} {{ $half }}</div>
        <div class="text-2xl font-bold text-red-700">&#8377;{{ number_format($total, 2) }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ $rows->count() }} contributions • LWF is half-yearly in most states (Jun & Dec deposits)</div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Employee</th><th>State</th><th>EE Contribution</th><th>ER Contribution</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->employee_name ?? $r->emp_id ?? '—' }}</td>
                        <td>{{ $r->state ?? '—' }}</td>
                        <td>&#8377;{{ number_format($r->employee_contribution ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->employer_contribution ?? 0, 2) }}</td>
                        <td>&#8377;{{ number_format($r->total_contribution ?? 0, 2) }}</td>
                        <td><span class="pill {{ ($r->status ?? '') === 'Paid' ? 'pill-ok' : 'pill-warn' }}">{{ $r->status ?? 'Pending' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6 text-slate-500">
                        No LWF records for {{ $year }} {{ $half }}.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
