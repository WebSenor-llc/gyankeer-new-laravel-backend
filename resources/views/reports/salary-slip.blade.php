@extends('layouts.app')
@section('title', 'Salary Slip')
@section('content')
<div class="p-4 max-w-4xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">Salary Slip</span></div>
    <h1 class="text-xl font-bold mb-3">Salary Slip</h1>

    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Employee</label>
            <select name="emp_id" class="border border-[var(--line)] rounded p-2 text-sm" style="min-width:240px">
                <option value="">— Select —</option>
                @foreach($employees as $e)
                    <option value="{{ $e->emp_id }}" @selected((string)$empId === (string)$e->emp_id)>{{ $e->full_name ?? $e->emp_id }}</option>
                @endforeach
            </select></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
            <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                    <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                @endforeach
            </select></div>
        <button type="submit" class="tb-btn primary">Generate</button>
    </form>

    @if($payslip)
        <div class="card p-5">
            <h2 class="font-bold mb-3">Payslip — {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h2>
            <table class="grid-tbl">
                <tr><th>Basic</th><td>&#8377;{{ number_format($payslip->basic, 2) }}</td></tr>
                <tr><th>HRA</th><td>&#8377;{{ number_format($payslip->hra, 2) }}</td></tr>
                <tr><th>DA</th><td>&#8377;{{ number_format($payslip->da, 2) }}</td></tr>
                <tr><th>Special Allowance</th><td>&#8377;{{ number_format($payslip->spl_allow, 2) }}</td></tr>
                <tr><th>Gross Earnings</th><td><strong>&#8377;{{ number_format($payslip->gross_earnings, 2) }}</strong></td></tr>
                <tr><th>Net Pay</th><td><strong style="color:var(--brand)">&#8377;{{ number_format($payslip->net_pay ?? 0, 2) }}</strong></td></tr>
            </table>
        </div>
    @elseif($empId)
        <div class="card p-6 text-center text-slate-500">No payslip for this employee in this period.</div>
    @else
        <div class="card p-6 text-center text-slate-500">Select an employee and period to view their payslip.</div>
    @endif
</div>
@endsection
