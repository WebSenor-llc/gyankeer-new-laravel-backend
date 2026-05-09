@extends('layouts.app')
@section('title', 'Employee Self-Service')

@section('content')
<div class="p-4 max-w-5xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">Self-Service / <span class="text-slate-900 font-semibold">Dashboard</span></div>

    @if(!$emp)
        <div class="card p-6 text-center text-slate-500">No employee data available.</div>
    @else
    <div class="card p-5 mb-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full grad-red text-white text-xl font-bold flex items-center justify-center">
                {{ strtoupper(substr($emp->full_name ?? 'U', 0, 1)) }}
            </div>
            <div class="flex-1">
                <div class="text-lg font-bold">{{ $emp->full_name }}</div>
                <div class="text-sm text-slate-500">{{ $emp->designation->designation_name ?? '—' }} • {{ $emp->department->dept_name ?? '—' }}</div>
            </div>
            <div class="text-right">
                <div class="text-[11px] text-slate-500 uppercase">Emp ID</div>
                <div class="font-semibold">{{ $emp->emp_id }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <a href="{{ route('ess.payslip') }}" class="card p-5 hover:shadow transition">
            <div class="text-3xl mb-1">💰</div>
            <div class="font-semibold mb-1">Latest Payslip</div>
            @if($latestPayslip)
                <div class="text-xs text-slate-500">{{ \DateTime::createFromFormat('!m', $latestPayslip->period_month)->format('M') }} {{ $latestPayslip->period_year }} • Net &#8377;{{ number_format($latestPayslip->net_pay ?? 0, 0) }}</div>
            @else
                <div class="text-xs text-slate-500">No payslip available yet</div>
            @endif
        </a>
        <a href="{{ route('ess.it-decl') }}" class="card p-5 hover:shadow transition">
            <div class="text-3xl mb-1">📊</div>
            <div class="font-semibold mb-1">IT Declaration</div>
            <div class="text-xs text-slate-500">Tax regime: {{ $emp->tax_regime ?? 'New' }}</div>
        </a>
        <a href="{{ route('ess.form16') }}" class="card p-5 hover:shadow transition">
            <div class="text-3xl mb-1">📄</div>
            <div class="font-semibold mb-1">Form 16</div>
            <div class="text-xs text-slate-500">Annual TDS certificate</div>
        </a>
    </div>

    <div class="card p-4 mb-4">
        <h2 class="font-semibold mb-2">Leave Balance</h2>
        @if($leaveBalance->isEmpty())
            <div class="text-sm text-slate-500">No leave balance records yet.</div>
        @else
            <table class="grid-tbl">
                <thead><tr><th>Type</th><th>Earned</th><th>Used</th><th>Balance</th></tr></thead>
                <tbody>
                    @foreach($leaveBalance as $b)
                        <tr>
                            <td>{{ $b->leave_type_id ?? '—' }}</td>
                            <td>{{ $b->earned ?? 0 }}</td>
                            <td>{{ $b->used ?? 0 }}</td>
                            <td><strong>{{ ($b->earned ?? 0) - ($b->used ?? 0) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card p-4">
        <h2 class="font-semibold mb-2">Recent Leave Applications</h2>
        @if($recentLeaves->isEmpty())
            <div class="text-sm text-slate-500">No leave applications submitted.</div>
        @else
            <table class="grid-tbl">
                <thead><tr><th>From</th><th>To</th><th>Type</th><th>Days</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($recentLeaves as $l)
                        <tr>
                            <td>{{ $l->from_date }}</td>
                            <td>{{ $l->to_date }}</td>
                            <td>{{ $l->leave_type_id }}</td>
                            <td>{{ $l->total_days }}</td>
                            <td><span class="pill {{ ($l->status ?? '') === 'Approved' ? 'pill-ok' : (($l->status ?? '') === 'Rejected' ? 'pill-bad' : 'pill-warn') }}">{{ $l->status ?? 'Pending' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @endif
</div>
@endsection
