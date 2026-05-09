@extends('layouts.app')
@section('title', 'Gratuity Register')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">Gratuity</span></div>
    <h1 class="text-xl font-bold mb-1">Gratuity Register (Payment of Gratuity Act 1972)</h1>
    <p class="text-xs text-slate-500 mb-3">Eligibility: 5+ years of continuous service. Formula: <code>(Last Basic + DA) × 15 × Years ÷ 26</code>. Tax-free up to &#8377;20 lakh.</p>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Name</th><th>Date of Joining</th><th>Years</th><th>Eligible</th><th>Provision Amount</th></tr></thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>{{ $r['emp_id'] }}</td>
                        <td>{{ $r['name'] ?? '—' }}</td>
                        <td>{{ $r['doj'] ? \Carbon\Carbon::parse($r['doj'])->format('d-M-Y') : '—' }}</td>
                        <td>{{ $r['years'] }}</td>
                        <td>
                            @if($r['eligible'])
                                <span class="pill pill-ok">Eligible</span>
                            @else
                                <span class="pill pill-warn">Not eligible</span>
                            @endif
                        </td>
                        <td>&#8377;{{ number_format($r['amount'], 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6 text-slate-500">
                        No active employees yet.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
