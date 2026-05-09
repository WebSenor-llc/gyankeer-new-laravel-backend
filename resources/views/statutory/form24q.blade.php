@extends('layouts.app')
@section('title', 'Form 24Q')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">Form 24Q</span></div>
    <h1 class="text-xl font-bold mb-3">Form 24Q — Quarterly TDS Return</h1>

    <form method="GET" class="card p-3 mb-4 flex gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Financial Year</label>
            <input type="text" name="fy" value="{{ $fy }}" placeholder="2025-26" class="border border-[var(--line)] rounded p-2 text-sm" style="width:120px"/></div>
        <button type="submit" class="tb-btn primary">Apply</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr>
                <th>Quarter</th><th>Employee</th><th>PAN</th>
                <th>Gross Paid</th><th>TDS Deducted</th><th>Tax Paid</th>
                <th>Filing Date</th><th>Ack No.</th><th>Status</th>
            </tr></thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>{{ $r->quarter ?? '—' }}</td>
                        <td>{{ $r->employee_name ?? '—' }}</td>
                        <td>{{ $r->deductee_pan ?? '—' }}</td>
                        <td>&#8377;{{ number_format($r->total_gross_paid ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->total_deduction ?? 0, 0) }}</td>
                        <td>&#8377;{{ number_format($r->total_tax_paid ?? 0, 0) }}</td>
                        <td>{{ $r->filing_date ?? '—' }}</td>
                        <td>{{ $r->filing_ack_no ?? '—' }}</td>
                        <td><span class="pill {{ ($r->status ?? '') === 'Filed' ? 'pill-ok' : 'pill-warn' }}">{{ $r->status ?? 'Pending' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-6 text-slate-500">
                        No Form 24Q records for FY {{ $fy }}. Form 24Q is filed quarterly:
                        Q1 by 31-Jul, Q2 by 31-Oct, Q3 by 31-Jan, Q4 by 31-May.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
