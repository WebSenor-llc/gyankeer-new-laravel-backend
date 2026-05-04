@extends('layouts.app')
@section('title', 'Salary Runs')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Payroll / <span class="text-slate-900 font-semibold">Salary Runs</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Salary Runs</h1>
        <a href="{{ route('payroll.runs.create') }}" class="tb-btn primary">+ New Salary Run</a>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    <th>Run Code</th><th>Period</th><th>Company</th><th>Eligible</th>
                    <th>Earnings</th><th>Deductions</th><th>Net Payout</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($runs as $r)
                    <tr>
                        <td class="font-semibold">{{ $r->run_code }}</td>
                        <td>{{ \DateTime::createFromFormat('!m', $r->period_month)->format('M') }} {{ $r->period_year }}</td>
                        <td>{{ $r->company->company_name ?? ('#'.$r->company_id) }}</td>
                        <td>{{ (int) $r->eligible_emp_count }}</td>
                        <td>&#8377;{{ number_format($r->total_earnings, 2) }}</td>
                        <td>&#8377;{{ number_format($r->total_deductions, 2) }}</td>
                        <td><strong>&#8377;{{ number_format($r->total_net_payout, 2) }}</strong></td>
                        <td>
                            @if($r->status === 'Posted')<span class="pill pill-ok">Posted</span>
                            @elseif($r->status === 'Approved')<span class="pill pill-info">Approved</span>
                            @elseif($r->status === 'Draft')<span class="pill pill-warn">Draft</span>
                            @else<span class="pill pill-bad">{{ $r->status }}</span>@endif
                        </td>
                        <td><a href="{{ route('payroll.runs.show', $r->run_id) }}" class="tb-btn">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-6 text-slate-500">
                        No salary runs yet. <a href="{{ route('payroll.runs.create') }}" class="text-[var(--brand)] font-semibold">Create the first run &rarr;</a>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($runs->hasPages())<div class="mt-3">{{ $runs->links() }}</div>@endif
</div>
@endsection
