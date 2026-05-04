@extends('layouts.app')
@section('title', 'My Payslips')

@section('content')
<div class="p-4 max-w-5xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">Self-Service / <span class="text-slate-900 font-semibold">Payslips</span></div>
    <h1 class="text-xl font-bold mb-3">My Payslips</h1>

    @if(!$emp)
        <div class="card p-6 text-center text-slate-500">No employee data.</div>
    @elseif($payslips->isEmpty())
        <div class="card p-6 text-center text-slate-500">
            <div class="text-3xl mb-2">📭</div>
            <p class="font-semibold">No payslips yet.</p>
            <p class="text-xs mt-2">Payslips will appear here once a salary run is posted for your employee record.</p>

            <div class="mt-6 text-left max-w-2xl mx-auto">
                <h3 class="font-semibold text-sm mb-2">Sample Payslip Format (Indian Govt. Rules)</h3>
                @include('ess._payslip_sample', ['emp' => $emp])
            </div>
        </div>
    @else
        @foreach($payslips as $p)
            @include('ess._payslip', ['p' => $p, 'emp' => $emp])
        @endforeach
    @endif
</div>
@endsection
