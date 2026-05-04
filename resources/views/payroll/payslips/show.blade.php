@extends('layouts.app')
@section('title', 'Payslip — '.$payslip->emp->full_name)
@section('content')
<div class="p-4 max-w-4xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        Payroll /
        <a href="{{ route('payroll.payslips.index', ['year'=>$payslip->period_year,'month'=>$payslip->period_month]) }}" class="hover:underline">Payslips</a> /
        <span class="text-slate-900 font-semibold">{{ $payslip->emp->full_name }} — {{ \DateTime::createFromFormat('!m', $payslip->period_month)->format('F') }} {{ $payslip->period_year }}</span>
    </div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Payslip</h1>
        <div class="flex gap-2">
            <a href="{{ route('payroll.payslips.print', [$payslip->emp_id, $payslip->period_year, $payslip->period_month]) }}" target="_blank" class="tb-btn primary">🖨 Print / Download PDF</a>
            <a href="{{ route('payroll.payslips.index', ['year'=>$payslip->period_year,'month'=>$payslip->period_month]) }}" class="tb-btn">← Back</a>
        </div>
    </div>

    @include('payroll.payslips._slip', ['payslip' => $payslip, 'company' => $company])

    <p class="text-[11px] text-slate-500 mt-3">
        💡 Tip: Click "Print / Download PDF" → in the print dialog, choose <strong>Save as PDF</strong> to download a PDF copy. The print version has no navigation chrome.
    </p>
</div>
@endsection
