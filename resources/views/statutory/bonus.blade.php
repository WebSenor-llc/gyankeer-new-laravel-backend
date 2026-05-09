@extends('layouts.app')
@section('title', 'Bonus Provision')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">Bonus</span></div>
    <h1 class="text-xl font-bold mb-1">Bonus Provision (Payment of Bonus Act 1965)</h1>
    <p class="text-xs text-slate-500 mb-3">Statutory bonus is 8.33%–20% of wages, capped at &#8377;7,000/month wage ceiling. Eligible: employees with monthly wage ≤ &#8377;21,000.</p>

    <form method="GET" class="card p-3 mb-4 flex gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Financial Year</label>
            <input type="text" name="fy" value="{{ $fy }}" placeholder="2025-26" class="border border-[var(--line)] rounded p-2 text-sm" style="width:120px"/></div>
        <button type="submit" class="tb-btn primary">Apply</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr>
                <th>Employee</th><th>Eligible</th><th>Basic+DA (Monthly)</th><th>Capped Wage (₹7k)</th>
                <th>Bonus %</th><th>Annual Bonus</th><th>Status</th>
            </tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->employee_name ?? $r->emp_id ?? '—' }}</td>
                        <td>
                            @if($r->eligible_flag)
                                <span class="pill pill-ok">Yes</span>
                            @else
                                <span class="pill pill-warn">No</span>
                            @endif
                        </td>
                        <td>&#8377;{{ number_format($r->monthly_basic_da ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->bonus_wage_capped_7k ?? 0, 0) }}</td>
                        <td>{{ $r->bonus_percent ?? '—' }}%</td>
                        <td>&#8377;{{ number_format($r->annual_bonus_amount ?? 0, 0) }}</td>
                        <td><span class="pill {{ ($r->status ?? '') === 'Paid' ? 'pill-ok' : 'pill-warn' }}">{{ $r->status ?? 'Pending' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6 text-slate-500">
                        No bonus provisions for FY {{ $fy }}.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
