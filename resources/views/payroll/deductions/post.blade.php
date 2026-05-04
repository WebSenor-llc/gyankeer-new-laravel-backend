@extends('layouts.app')
@section('title', 'Post Deductions')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Payroll / <span class="text-slate-900 font-semibold">Post Deductions</span></div>
    <h1 class="text-xl font-bold mb-1">Post Deductions &mdash; Loans &amp; Advances</h1>
    <p class="text-xs text-slate-500 mb-3">Active loan/advance EMIs that get auto-deducted from each monthly payroll.</p>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Employee</th><th>Loan Type</th><th>Principal</th><th>Outstanding</th><th>Monthly EMI</th><th>Remaining (months)</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($loans as $l)
                    <tr>
                        <td>{{ $l->emp_id }}</td>
                        <td>{{ $l->employee_name }}</td>
                        <td>{{ $l->loan_type ?? '—' }}</td>
                        <td>&#8377;{{ number_format($l->principal, 2) }}</td>
                        <td>&#8377;{{ number_format($l->outstanding_principal, 2) }}</td>
                        <td>&#8377;{{ number_format($l->emi_amount, 2) }}</td>
                        <td>{{ $l->emi_amount > 0 ? round($l->outstanding_principal / $l->emi_amount) : '—' }}</td>
                        <td><span class="pill {{ $l->repayment_status === 'Closed' ? 'pill-ok' : 'pill-warn' }}">{{ $l->repayment_status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-6 text-slate-500">No active loans / advances. Add one from <a href="{{ route('loans.index') }}" class="text-[var(--brand)] font-semibold">Loans &amp; Advances</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($loans->hasPages())<div class="mt-3">{{ $loans->links() }}</div>@endif
</div>
@endsection
