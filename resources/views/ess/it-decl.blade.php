@extends('layouts.app')
@section('title', 'IT Declaration')

@section('content')
<div class="p-4 max-w-3xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">Self-Service / <span class="text-slate-900 font-semibold">IT Declaration</span></div>
    <h1 class="text-xl font-bold mb-3">Income Tax Declaration (FY 2025-26)</h1>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('ess.it-decl.save') }}" class="card p-5 space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tax Regime</label>
            <select name="tax_regime" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                <option value="New" @selected(($emp->tax_regime ?? 'New') === 'New')>New (default, lower rates, no exemptions)</option>
                <option value="Old" @selected(($emp->tax_regime ?? '') === 'Old')>Old (higher rates, with exemptions)</option>
            </select>
        </div>

        <div>
            <h2 class="font-semibold text-sm mb-2 mt-4">Old Regime Deductions (only if Old Regime selected)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div><label class="block text-xs font-semibold text-slate-600 mb-1.5">80C (PF, ELSS, LIC, PPF — max ₹1.5L)</label><input type="number" name="sec_80c" step="0.01" value="{{ $emp->sec_80c_declared ?? '' }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1.5">80D (Mediclaim — max ₹25k self / ₹50k senior)</label><input type="number" name="sec_80d" step="0.01" value="{{ $emp->sec_80d_declared ?? '' }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1.5">80CCD(1B) (NPS extra — max ₹50k)</label><input type="number" name="sec_80ccd1b" step="0.01" value="{{ $emp->sec_80ccd1b_declared ?? '' }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1.5">80E (Education Loan Interest)</label><input type="number" name="sec_80e" step="0.01" value="{{ $emp->sec_80e_declared ?? '' }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1.5">24B (Home Loan Interest — max ₹2L)</label><input type="number" name="sec_24b" step="0.01" value="{{ $emp->sec_24b_declared ?? '' }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1.5">HRA Rent Paid (Annual)</label><input type="number" name="hra_rent" step="0.01" value="{{ $emp->hra_rent_paid_annual ?? '' }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm"></div>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-[var(--line)]">
            <a href="{{ route('ess.index') }}" class="tb-btn">Cancel</a>
            <button type="submit" class="tb-btn primary">Save Declaration</button>
        </div>
    </form>
</div>
@endsection
