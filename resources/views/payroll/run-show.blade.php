@extends('layouts.app')
@section('title', $run->run_code)

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">
        Payroll / <a href="{{ route('payroll.runs.index') }}" class="hover:underline">Salary Runs</a> /
        <span class="text-slate-900 font-semibold">{{ $run->run_code }}</span>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="card p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-xl font-bold">{{ $run->run_code }}</h1>
                <div class="text-sm text-slate-500 mt-1">
                    {{ \DateTime::createFromFormat('!m', $run->period_month)->format('F') }} {{ $run->period_year }}
                    • {{ $run->company->company_name ?? '—' }}
                    @if($run->status === 'Posted')<span class="pill pill-ok">Posted</span>
                    @elseif($run->status === 'Approved')<span class="pill pill-info">Approved</span>
                    @elseif($run->status === 'Computed')<span class="pill pill-info">Computed</span>
                    @else<span class="pill pill-warn">{{ $run->status }}</span>@endif
                </div>
            </div>
            <div class="flex gap-2">
                {{-- Run Payroll Engine: visible whenever payslips not yet generated, OR explicitly recomputable in Draft/Computed --}}
                @if($run->status !== 'Posted' && ($payslipCount === 0 || in_array($run->status, ['Draft', 'Computed'])))
                    <form method="POST" action="{{ route('payroll.runs.compute', $run->run_id) }}" class="inline" onsubmit="return confirm('Run payroll engine? This will (re)compute payslips for every active employee in this company.')">
                        @csrf<button class="tb-btn primary">{{ $payslipCount > 0 ? 'Recompute' : 'Run Payroll Engine' }}</button>
                    </form>
                @endif
                @if(in_array($run->status, ['Draft', 'Computed']))
                    <form method="POST" action="{{ route('payroll.runs.approve', $run->run_id) }}" class="inline">
                        @csrf<button class="tb-btn">Approve</button>
                    </form>
                @endif
                @if($run->status === 'Approved')
                    <form method="POST" action="{{ route('payroll.runs.post', $run->run_id) }}" class="inline">
                        @csrf<button class="tb-btn primary">Post to GL</button>
                    </form>
                @endif
                <form method="GET" action="{{ route('payroll.runs.bank-file', $run->run_id) }}" class="inline">
                    <button class="tb-btn">Generate Bank File</button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 text-sm">
            <div><div class="text-[11px] text-slate-500 uppercase">Eligible Employees</div><div class="text-lg font-bold">{{ (int) $run->eligible_emp_count }}</div></div>
            <div><div class="text-[11px] text-slate-500 uppercase">Payslips Generated</div><div class="text-lg font-bold">{{ $payslipCount }}</div></div>
            <div><div class="text-[11px] text-slate-500 uppercase">Total Earnings</div><div class="text-lg font-bold">&#8377;{{ number_format($run->total_earnings, 0) }}</div></div>
            <div><div class="text-[11px] text-slate-500 uppercase">Total Deductions</div><div class="text-lg font-bold">&#8377;{{ number_format($run->total_deductions, 0) }}</div></div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card p-4">
            <h2 class="font-semibold mb-2">Statutory Totals</h2>
            <table class="grid-tbl">
                <tr><th>PF (Employee 12%)</th><td>&#8377;{{ number_format($run->total_pf_emp, 0) }}</td></tr>
                <tr><th>PF (Employer 3.67%)</th><td>&#8377;{{ number_format($run->total_pf_er, 0) }}</td></tr>
                <tr><th>EPS (8.33%)</th><td>&#8377;{{ number_format($run->total_eps, 0) }}</td></tr>
                <tr><th>EDLI (0.5%)</th><td>&#8377;{{ number_format($run->total_edli, 0) }}</td></tr>
                <tr><th>PF Admin (0.5%)</th><td>&#8377;{{ number_format($run->total_admin, 0) }}</td></tr>
                <tr><th>ESI (Employee 0.75%)</th><td>&#8377;{{ number_format($run->total_esi_emp, 0) }}</td></tr>
                <tr><th>ESI (Employer 3.25%)</th><td>&#8377;{{ number_format($run->total_esi_er, 0) }}</td></tr>
                <tr><th>Professional Tax</th><td>&#8377;{{ number_format($run->total_pt, 0) }}</td></tr>
                <tr><th>LWF (Employee + Employer)</th><td>&#8377;{{ number_format($run->total_lwf_emp + $run->total_lwf_er, 0) }}</td></tr>
                <tr><th>TDS</th><td>&#8377;{{ number_format($run->total_tds, 0) }}</td></tr>
            </table>
        </div>

        <div class="card p-4">
            <h2 class="font-semibold mb-2">Provisions</h2>
            <table class="grid-tbl">
                <tr><th>Bonus Provision</th><td>&#8377;{{ number_format($run->total_bonus_provision, 0) }}</td></tr>
                <tr><th>Gratuity Provision</th><td>&#8377;{{ number_format($run->total_gratuity_provision, 0) }}</td></tr>
            </table>

            <div class="card p-4 mt-3" style="background:#FEF2F2;border-color:#FCA5A5">
                <div class="text-[11px] text-red-700 uppercase font-semibold">Total Net Payout</div>
                <div class="text-2xl font-bold text-red-700">&#8377;{{ number_format($run->total_net_payout, 0) }}</div>
                <div class="text-xs text-slate-500 mt-1">Total Employer Cost (CTC): &#8377;{{ number_format($run->total_employer_cost, 0) }}</div>
            </div>
        </div>
    </div>

    @if($payslipCount === 0)
        <div class="card p-6 mt-4 text-center text-slate-500">
            <div class="text-4xl mb-2">⚙️</div>
            <div class="font-semibold mb-1">No payslips generated yet for this run.</div>
            <p class="text-xs">Click <strong>"Run Payroll Engine"</strong> above to compute payslips for every active employee using the configured EPF / ESI / PT / LWF / TDS rules.</p>
        </div>
    @else
        <div class="card mt-4 overflow-x-auto">
            <div class="p-3 border-b border-[var(--line)] font-semibold">Payslips ({{ $payslipCount }})</div>
            <table class="grid-tbl">
                <thead><tr><th>Emp ID</th><th>Basic</th><th>HRA</th><th>DA</th><th>Gross</th><th>EPF</th><th>ESI</th><th>PT</th><th>TDS</th><th>Net Pay</th></tr></thead>
                <tbody>
                @foreach(\App\Models\Payslip::where('run_id', $run->run_id)->take(50)->get() as $p)
                    <tr>
                        <td>{{ $p->emp_id }}</td>
                        <td>&#8377;{{ number_format($p->basic, 0) }}</td>
                        <td>&#8377;{{ number_format($p->hra, 0) }}</td>
                        <td>&#8377;{{ number_format($p->da, 0) }}</td>
                        <td>&#8377;{{ number_format($p->gross_earnings, 0) }}</td>
                        <td>&#8377;{{ number_format($p->epf_emp, 0) }}</td>
                        <td>&#8377;{{ number_format($p->esi_emp, 0) }}</td>
                        <td>&#8377;{{ number_format($p->pt, 0) }}</td>
                        <td>&#8377;{{ number_format($p->tds, 0) }}</td>
                        <td><strong>&#8377;{{ number_format($p->net_pay, 0) }}</strong></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if($payslipCount > 50)<div class="p-3 text-xs text-slate-500">Showing first 50 of {{ $payslipCount }}</div>@endif
        </div>
    @endif
</div>
@endsection
