@extends('layouts.app')
@section('title', 'TDS Estimate')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">TDS</span></div>
    <h1 class="text-xl font-bold mb-3">Income Tax (TDS) — Annual Estimate</h1>

    <p class="text-xs text-slate-500 mb-3">Estimates use a simplified slab. Actual TDS is computed by the payroll engine each month.</p>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    <th>Emp ID</th><th>Name</th><th>PAN</th><th>Regime</th>
                    <th>Annual Gross</th><th>Estimated Annual Tax</th><th>Monthly TDS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>{{ $r['emp_id'] }}</td>
                        <td>{{ $r['name'] ?? '—' }}</td>
                        <td>{{ $r['pan'] ?? '—' }}</td>
                        <td><span class="pill pill-info">{{ $r['regime'] }}</span></td>
                        <td>&#8377;{{ number_format($r['annual_gross'], 2) }}</td>
                        <td>&#8377;{{ number_format($r['annual_tax'], 2) }}</td>
                        <td>&#8377;{{ number_format($r['monthly_tds'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6 text-slate-500">
                        No active employees yet. Add employees from <a href="/employees/create" class="text-[var(--brand)] font-semibold">Manage Employee</a>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
