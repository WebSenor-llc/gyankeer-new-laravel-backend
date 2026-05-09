@extends('layouts.app')
@section('title', 'Form 16')

@section('content')
<div class="p-4 max-w-3xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">Form 16</span></div>
    <h1 class="text-xl font-bold mb-3">Form 16 — TDS Certificate</h1>

    @if($emp)
        <div class="card p-5 mb-4">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><div class="text-[11px] text-slate-500 uppercase">Employee</div><div class="font-semibold">{{ $emp->full_name }}</div></div>
                <div><div class="text-[11px] text-slate-500 uppercase">PAN</div><div class="font-semibold">{{ $emp->pan_no ?? '—' }}</div></div>
                <div><div class="text-[11px] text-slate-500 uppercase">Designation</div><div class="font-semibold">{{ $emp->designation->designation_name ?? '—' }}</div></div>
                <div><div class="text-[11px] text-slate-500 uppercase">Financial Year</div><div class="font-semibold">{{ $fy }}</div></div>
            </div>
        </div>
        <div class="card p-5">
            <h2 class="font-semibold mb-3">Annual Salary Summary (estimate)</h2>
            <table class="grid-tbl">
                <tr><th>Gross Salary (Annual)</th><td>&#8377;{{ number_format(($emp->current_gross ?? 0) * 12, 0) }}</td></tr>
                <tr><th>Standard Deduction</th><td>&#8377;50,000.00</td></tr>
                <tr><th>Taxable Income (est.)</th><td>&#8377;{{ number_format(max(0, ($emp->current_gross ?? 0) * 12 - 50000), 0) }}</td></tr>
                <tr><th>Tax Regime</th><td>{{ $emp->tax_regime ?? 'New' }}</td></tr>
            </table>
            <p class="text-[11px] text-slate-400 mt-3">Form 16 PDF generation requires the laravel-dompdf package. Once installed, this page will render a downloadable Part A + Part B PDF.</p>
        </div>
    @else
        <div class="card p-6 text-center text-slate-500">Employee not found.</div>
    @endif
</div>
@endsection
