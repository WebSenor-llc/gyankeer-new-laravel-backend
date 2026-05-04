@extends('layouts.app')
@section('title', 'Statistical Report')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">Statistical</span></div>
    <h1 class="text-xl font-bold mb-3">Statistical Report</h1>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="card p-4"><div class="text-[11px] text-slate-500 uppercase">Active Employees</div><div class="text-2xl font-bold">{{ $totals['employees'] }}</div></div>
        <div class="card p-4"><div class="text-[11px] text-slate-500 uppercase">Payslips</div><div class="text-2xl font-bold">{{ $totals['payslips'] }}</div></div>
        <div class="card p-4"><div class="text-[11px] text-slate-500 uppercase">Salary Runs</div><div class="text-2xl font-bold">{{ $totals['runs'] }}</div></div>
        <div class="card p-4"><div class="text-[11px] text-slate-500 uppercase">GL Transactions</div><div class="text-2xl font-bold">{{ $totals['txns'] }}</div></div>
    </div>
</div>
@endsection
