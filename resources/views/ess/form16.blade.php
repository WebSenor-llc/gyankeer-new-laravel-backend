@extends('layouts.app')
@section('title', 'Form 16')

@section('content')
<div class="p-4 max-w-3xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">Self-Service / <span class="text-slate-900 font-semibold">Form 16</span></div>
    <h1 class="text-xl font-bold mb-3">Form 16 — TDS Certificate (FY {{ $fy }})</h1>

    <form method="GET" class="card p-3 mb-4 flex gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">FY</label>
            <input type="text" name="fy" value="{{ $fy }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:100px"/></div>
        <button type="submit" class="tb-btn primary">Apply</button>
    </form>

    @if($emp)
        <div class="card p-5">
            <div class="grid grid-cols-2 gap-3 text-sm mb-3 pb-3 border-b border-[var(--line)]">
                <div><div class="text-[11px] text-slate-500 uppercase">Employee</div><div class="font-semibold">{{ $emp->full_name }}</div></div>
                <div><div class="text-[11px] text-slate-500 uppercase">PAN</div><div class="font-semibold">{{ $emp->pan_no ?? '—' }}</div></div>
                <div><div class="text-[11px] text-slate-500 uppercase">Tax Regime</div><div class="font-semibold">{{ $emp->tax_regime ?? 'New' }}</div></div>
                <div><div class="text-[11px] text-slate-500 uppercase">Designation</div><div class="font-semibold">{{ $emp->designation->designation_name ?? '—' }}</div></div>
            </div>

            <h2 class="font-semibold text-sm mb-2">Salary Summary (Estimated for FY {{ $fy }})</h2>
            <table class="grid-tbl">
                <tr><th>Annual Gross</th><td>&#8377;{{ number_format(($emp->current_gross ?? 0) * 12, 0) }}</td></tr>
                <tr><th>Standard Deduction (Sec 16(ia))</th><td>&#8377;75,000.00</td></tr>
                <tr><th>Professional Tax</th><td>&#8377;2,400.00</td></tr>
                <tr><th>Net Taxable Income (est.)</th><td><strong>&#8377;{{ number_format(max(0, ($emp->current_gross ?? 0) * 12 - 77400), 0) }}</strong></td></tr>
            </table>

            <p class="text-[11px] text-slate-400 mt-3">
                A signed Form 16 PDF (Part A from TRACES + Part B from payroll) will be issuable here once the laravel-dompdf package is installed. Indian compliance: must be issued to all employees with TDS deducted by 15-Jun following FY end.
            </p>
        </div>
    @else
        <div class="card p-6 text-center text-slate-500">No employee data.</div>
    @endif
</div>
@endsection
