@extends('layouts.app')

@section('title', 'Employee — Salary')

@php
    $structures = \App\Models\SalaryStructure::where('emp_id', $emp->emp_id)
        ->orderByDesc('effective_from')
        ->get();
    $events = \App\Models\EmployeeCareerEvent::where('emp_id', $emp->emp_id)
        ->whereIn('event_type', ['Increment', 'Increment v2'])
        ->orderByDesc('event_date')
        ->limit(20)
        ->get();
    $today = today()->toDateString();
    $fmt = fn($v) => '₹ ' . number_format((float) $v, 2);
@endphp

@section('content')
<div class="p-6 space-y-4">
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">{{ $emp->full_name }} — Salary</h1>
                <p class="text-xs text-slate-500">Emp ID: {{ $emp->emp_id }} · Salary Group: {{ $emp->salary_group->salary_group_name ?? '—' }}</p>
            </div>
            <a href="{{ route('manage-salary.config', $emp->emp_id) }}" class="tb-btn primary" style="background:#16A34A;border-color:#15803D">✏️ Manage Salary</a>
        </div>
    </div>

    <div class="card p-4">
        <h2 class="text-sm font-bold mb-2 pb-1 border-b border-[var(--line)]">Current (live in payroll)</h2>
        <div class="grid grid-cols-4 gap-3 text-sm">
            <div><span class="text-slate-500">Basic</span><br><b>{{ $fmt($emp->current_basic) }}</b></div>
            <div><span class="text-slate-500">HRA</span><br><b>{{ $fmt($emp->current_hra) }}</b></div>
            <div><span class="text-slate-500">DA</span><br><b>{{ $fmt($emp->current_da) }}</b></div>
            <div><span class="text-slate-500">Conveyance</span><br><b>{{ $fmt($emp->current_conv) }}</b></div>
            <div><span class="text-slate-500">Medical</span><br><b>{{ $fmt($emp->current_med) }}</b></div>
            <div><span class="text-slate-500">Special / Other</span><br><b>{{ $fmt($emp->current_spl) }}</b></div>
            <div><span class="text-slate-500">Gross</span><br><b class="text-green-700">{{ $fmt($emp->current_gross) }}</b></div>
            <div><span class="text-slate-500">Last Increment</span><br><b>{{ $emp->last_increment_date ? \Carbon\Carbon::parse($emp->last_increment_date)->format('d-M-Y') : '—' }} @if($emp->last_increment_pct) ({{ $emp->last_increment_pct }}%) @endif</b></div>
        </div>
    </div>

    <div class="card p-4">
        <h2 class="text-sm font-bold mb-2 pb-1 border-b border-[var(--line)]">Salary Structure History ({{ $structures->count() }})</h2>
        @if($structures->isEmpty())
            <p class="text-xs text-slate-500">No structure rows yet. Values above come from the employee master (imported).</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="border p-2 text-left">Version</th>
                        <th class="border p-2 text-left">Effective</th>
                        <th class="border p-2 text-left">Status</th>
                        <th class="border p-2 text-right">Basic</th>
                        <th class="border p-2 text-right">HRA</th>
                        <th class="border p-2 text-right">DA</th>
                        <th class="border p-2 text-right">Conv</th>
                        <th class="border p-2 text-right">Med</th>
                        <th class="border p-2 text-right">Spl</th>
                        <th class="border p-2 text-right">Gross</th>
                        <th class="border p-2 text-left">Reason</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($structures as $s)
                    @php
                        $gross = (float)($s->basic + $s->hra + $s->da + $s->conveyance + $s->medical + $s->special_allowance);
                        $isFuture = $s->effective_from && $s->effective_from > $today;
                        $isCurrent = $s->effective_from && $s->effective_from <= $today && (!$s->effective_to || $s->effective_to >= $today);
                    @endphp
                    <tr class="{{ $isCurrent ? 'bg-green-50' : ($isFuture ? 'bg-amber-50' : '') }}">
                        <td class="border p-2">v{{ $s->version ?? '—' }}</td>
                        <td class="border p-2">
                            {{ $s->effective_from ? \Carbon\Carbon::parse($s->effective_from)->format('d-M-Y') : '—' }}
                            → {{ $s->effective_to ? \Carbon\Carbon::parse($s->effective_to)->format('d-M-Y') : 'open' }}
                        </td>
                        <td class="border p-2">
                            {{ $s->status ?? '—' }}
                            @if($isCurrent)<span class="text-green-700 font-semibold">· Current</span>@endif
                            @if($isFuture)<span class="text-amber-700 font-semibold">· Scheduled</span>@endif
                        </td>
                        <td class="border p-2 text-right">{{ number_format((float)$s->basic, 0) }}</td>
                        <td class="border p-2 text-right">{{ number_format((float)$s->hra, 0) }}</td>
                        <td class="border p-2 text-right">{{ number_format((float)$s->da, 0) }}</td>
                        <td class="border p-2 text-right">{{ number_format((float)$s->conveyance, 0) }}</td>
                        <td class="border p-2 text-right">{{ number_format((float)$s->medical, 0) }}</td>
                        <td class="border p-2 text-right">{{ number_format((float)$s->special_allowance, 0) }}</td>
                        <td class="border p-2 text-right font-bold">{{ number_format($gross, 0) }}</td>
                        <td class="border p-2 text-slate-600">{{ $s->restructure_reason ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    @if($events->isNotEmpty())
    <div class="card p-4">
        <h2 class="text-sm font-bold mb-2 pb-1 border-b border-[var(--line)]">Increment Events</h2>
        <table class="w-full text-xs border-collapse">
            <thead class="bg-slate-100">
                <tr>
                    <th class="border p-2 text-left">Date</th>
                    <th class="border p-2 text-left">Type</th>
                    <th class="border p-2 text-right">Old Gross</th>
                    <th class="border p-2 text-right">New Gross</th>
                    <th class="border p-2 text-right">Hike %</th>
                    <th class="border p-2 text-left">Remarks</th>
                </tr>
            </thead>
            <tbody>
            @foreach($events as $ev)
                <tr>
                    <td class="border p-2">{{ \Carbon\Carbon::parse($ev->event_date)->format('d-M-Y') }}</td>
                    <td class="border p-2">{{ $ev->event_type }}</td>
                    <td class="border p-2 text-right">{{ number_format((float)$ev->salary_old, 0) }}</td>
                    <td class="border p-2 text-right">{{ number_format((float)$ev->salary_new, 0) }}</td>
                    <td class="border p-2 text-right">{{ $ev->hike_percent }}%</td>
                    <td class="border p-2 text-slate-600">{{ $ev->remarks }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
