@extends('layouts.app')
@section('title', 'Statutory Settings')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Settings / <span class="text-slate-900 font-semibold">Statutory Rates</span></div>
    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Statutory Rates &amp; Slabs</h1>
        <form method="GET" class="flex gap-2 items-end">
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Financial Year</label>
                <select name="fy" onchange="this.form.submit()" class="border border-[var(--line)] rounded p-2 text-sm">
                    <option value="2024" @selected($fy==2024)>FY 2024-25</option>
                    <option value="2025" @selected($fy==2025)>FY 2025-26</option>
                    <option value="2026" @selected($fy==2026)>FY 2026-27</option>
                </select></div>
        </form>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <p class="text-xs text-slate-500 mb-4">Edit any rate below and click Save. Changes apply to <strong>future payroll computations only</strong> — existing payslips are unaffected. Recompute the Draft / Computed runs after saving.</p>

    <form method="POST" action="{{ route('settings.update', 'bulk') }}">
        @csrf
        @method('PATCH')

        @forelse($rates as $group => $items)
            <div class="card mb-4">
                <div class="p-3 border-b border-[var(--line)] font-semibold text-sm uppercase tracking-wide">
                    @switch($group)
                        @case('epf') EPF / EPS / EDLI / Admin @break
                        @case('esi') ESI @break
                        @case('bonus') Bonus (Payment of Bonus Act 1965) @break
                        @case('gratuity') Gratuity (Payment of Gratuity Act 1972) @break
                        @case('tds') TDS — §192 (Income Tax Act) @break
                        @default {{ strtoupper($group) }}
                    @endswitch
                </div>
                <table class="grid-tbl">
                    <thead><tr><th>Setting</th><th style="width:200px">Current Value</th></tr></thead>
                    <tbody>
                        @foreach($items as $r)
                            <tr>
                                <td>
                                    <div class="font-medium text-sm">{{ $r->label }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $r->key }}</div>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="value[{{ $r->id }}]" value="{{ rtrim(rtrim((string) $r->value_decimal, '0'), '.') }}"
                                           class="block w-full border border-[var(--line)] rounded p-2 text-sm focus:outline-none focus:border-[var(--brand)]"/>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="card p-6 text-center text-slate-500">No statutory rates seeded for FY {{ $fy }}-{{ $fy+1 }}. Run <code>php artisan db:seed --class=StatutorySlabSeeder</code>.</div>
        @endforelse

        @if($pt->isNotEmpty())
            <div class="card mb-4">
                <div class="p-3 border-b border-[var(--line)] font-semibold text-sm uppercase tracking-wide">Profession Tax — State Slabs</div>
                <table class="grid-tbl">
                    <thead><tr><th>State / Slab</th><th>Min Wage</th><th>Max Wage</th><th>PT Amount (₹)</th><th>Feb (extra)</th></tr></thead>
                    <tbody>
                        @foreach($pt as $r)
                            @php $j = $r->value_json ?? [] @endphp
                            <tr>
                                <td>{{ $r->key }}</td>
                                <td>&#8377;{{ number_format($j['min'] ?? 0) }}</td>
                                <td>{{ ($j['max'] ?? 0) > 999000 ? '∞' : '₹' . number_format($j['max'] ?? 0) }}</td>
                                <td><input type="number" step="0.01" name="value[{{ $r->id }}]" value="{{ rtrim(rtrim((string) $r->value_decimal, '0'), '.') }}" class="block w-full border border-[var(--line)] rounded p-1.5 text-sm"/></td>
                                <td>{{ $j['feb_amount'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($lwf->isNotEmpty())
            <div class="card mb-4">
                <div class="p-3 border-b border-[var(--line)] font-semibold text-sm uppercase tracking-wide">Labour Welfare Fund</div>
                <table class="grid-tbl">
                    <thead><tr><th>State</th><th>Frequency</th><th>EE Contrib</th><th>ER Contrib</th><th>Total (auto-calc)</th></tr></thead>
                    <tbody>
                        @foreach($lwf as $r)
                            @php $j = $r->value_json ?? [] @endphp
                            <tr>
                                <td>{{ $r->key }}</td>
                                <td>{{ $j['frequency'] ?? '—' }}</td>
                                <td>&#8377;{{ $j['employee'] ?? 0 }}</td>
                                <td>&#8377;{{ $j['employer'] ?? 0 }}</td>
                                <td><strong>&#8377;{{ rtrim(rtrim((string) $r->value_decimal, '0'), '.') }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($tds->isNotEmpty())
            <div class="card mb-4">
                <div class="p-3 border-b border-[var(--line)] font-semibold text-sm uppercase tracking-wide">TDS — Slabs (FY {{ $fy }}-{{ $fy+1 }})</div>
                <table class="grid-tbl">
                    <thead><tr><th>Regime / Slab</th><th>Min</th><th>Max</th><th>Rate (%)</th></tr></thead>
                    <tbody>
                        @foreach($tds as $r)
                            @php $j = $r->value_json ?? [] @endphp
                            <tr>
                                <td>{{ $r->key }}</td>
                                <td>&#8377;{{ number_format($j['min'] ?? 0) }}</td>
                                <td>{{ ($j['max'] ?? 0) > 99000000 ? '∞' : '₹' . number_format($j['max'] ?? 0) }}</td>
                                <td><input type="number" step="0.01" name="value[{{ $r->id }}]" value="{{ rtrim(rtrim((string) $r->value_decimal, '0'), '.') }}" class="block w-full border border-[var(--line)] rounded p-1.5 text-sm" style="width:100px"/></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="flex justify-end pt-3 border-t border-[var(--line)]">
            <button type="submit" class="tb-btn primary">Save All Changes</button>
        </div>
    </form>
</div>
@endsection
