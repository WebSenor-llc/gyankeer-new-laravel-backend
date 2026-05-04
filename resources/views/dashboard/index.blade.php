@extends('layouts.app')
@section('title','Dashboard')
@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold">Welcome back, {{ auth()->user()?->name ?? 'Admin' }} 👋</h1>
        <p class="text-sm text-slate-500">Period: <b>{{ now()->format('M Y') }}</b> · Closing: <b>{{ now()->endOfMonth()->format('d-M-Y') }}</b></p>
    </div>
    <a href="{{ route('payroll.runs.create') }}" class="tb-btn primary">+ Run Payroll</a>
</div>

<!-- KPI strip -->
<div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-4">
    @foreach([
        ['Headcount', $kpis['headcount'], 'Active employees'],
        ['Present',   $kpis['present'],   'Today'],
        ['On Leave',  $kpis['on_leave'],  'Today'],
        ['On Duty',   $kpis['on_duty'],   'Today'],
        ['Absent',    $kpis['absent'],    'Today'],
        ['Mismatch',  $kpis['mismatch'],  'Today'],
    ] as [$t,$v,$s])
    <div class="card p-4">
        <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">{{ $t }}</div>
        <div class="text-xl font-bold mt-1">{{ $v }}</div>
        <div class="text-xs text-slate-500 mt-0.5">{{ $s }}</div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-4 mb-4">
    <div class="card p-4">
        <div class="font-semibold text-sm mb-2">📊 Employees By Category</div>
        <canvas id="catChart" height="180"></canvas>
    </div>
    <div class="card p-4">
        <div class="font-semibold text-sm mb-2">🗓 Today Attendance Summary</div>
        <table class="w-full text-sm">
            <tr class="border-b"><td class="py-1.5 text-slate-500">Total</td><td class="py-1.5 text-right font-semibold">{{ $kpis['headcount'] }}</td></tr>
            <tr class="border-b"><td class="py-1.5 text-emerald-700">Present</td><td class="py-1.5 text-right font-semibold">{{ $kpis['present'] }}</td></tr>
            <tr class="border-b"><td class="py-1.5 text-amber-700">On Leave</td><td class="py-1.5 text-right">{{ $kpis['on_leave'] }}</td></tr>
            <tr class="border-b"><td class="py-1.5 text-blue-700">On Duty</td><td class="py-1.5 text-right">{{ $kpis['on_duty'] }}</td></tr>
            <tr class="bg-rose-50"><td class="py-1.5 text-rose-700 font-semibold">Absent / Mismatch</td><td class="py-1.5 text-right text-rose-700 font-bold">{{ $kpis['absent'] + $kpis['mismatch'] }}</td></tr>
        </table>
    </div>
    <div class="card p-4">
        <div class="font-semibold text-sm mb-2">💰 Payroll Breakdown by Department</div>
        <canvas id="payChart" height="160"></canvas>
    </div>
</div>

@if($latest_run)
<div class="card p-4 mb-4">
    <div class="flex items-center justify-between mb-2">
        <div class="font-semibold text-sm">Latest Run: {{ $latest_run->run_code }}</div>
        <span class="pill pill-{{ $latest_run->status === 'Posted' ? 'ok' : ($latest_run->status === 'Approved' ? 'info' : 'warn') }}">{{ $latest_run->status }}</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
        <div><div class="text-slate-500 text-xs">Eligible</div><div class="font-bold">{{ $latest_run->eligible_emp_count }}</div></div>
        <div><div class="text-slate-500 text-xs">Total Earnings</div><div class="font-bold">₹{{ number_format($latest_run->total_earnings, 0) }}</div></div>
        <div><div class="text-slate-500 text-xs">Total Deductions</div><div class="font-bold">₹{{ number_format($latest_run->total_deductions, 0) }}</div></div>
        <div><div class="text-slate-500 text-xs">Net Payout</div><div class="font-bold text-emerald-700">₹{{ number_format($latest_run->total_net_payout, 0) }}</div></div>
        <div><div class="text-slate-500 text-xs">CTC</div><div class="font-bold">₹{{ number_format($latest_run->total_employer_cost, 0) }}</div></div>
    </div>
</div>
@endif

@push('scripts')
<script>
const cat = document.getElementById('catChart');
new Chart(cat, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($employees_by_category)) !!},
        datasets: [{ data: {!! json_encode(array_values($employees_by_category)) !!}, backgroundColor: ['#16A34A','#F59E0B','#2563EB','#94A3B8','#7C2D12','#9333EA'], borderWidth: 0 }]
    },
    options: { cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
});

const pay = document.getElementById('payChart');
new Chart(pay, {
    type: 'bar',
    data: {
        labels: {!! json_encode($payroll_by_dept->pluck('dept_name')) !!},
        datasets: [{ label: 'Payroll ₹', data: {!! json_encode($payroll_by_dept->pluck('gross_sum')) !!}, backgroundColor: '#B91C1C' }]
    },
    options: { plugins: { legend: { display: false } } }
});
</script>
@endpush

@endsection
