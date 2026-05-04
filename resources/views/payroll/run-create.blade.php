@extends('layouts.app')
@section('title', 'Create Salary Run')

@section('content')
<div class="p-4 max-w-2xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        Payroll /
        <a href="{{ route('payroll.runs.index') }}" class="hover:underline">Salary Runs</a> /
        <span class="text-slate-900 font-semibold">Create</span>
    </div>
    <h1 class="text-xl font-bold mb-4">Create Salary Run</h1>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('payroll.runs.store') }}" class="card p-5 space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="company_id">Company *</label>
            <select id="company_id" name="company_id" required class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]">
                <option value="">— Select —</option>
                @foreach($companies as $c)
                    <option value="{{ $c->company_id }}" @selected(old('company_id') == $c->company_id || $c->company_id == session('active_company_id'))>{{ $c->company_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="period_year">Period Year *</label>
                <input id="period_year" name="period_year" type="number" required min="2020" max="2030" value="{{ old('period_year', now()->year) }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="period_month">Period Month *</label>
                <select id="period_month" name="period_month" required class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]">
                    @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                        <option value="{{ $n }}" @selected(old('period_month', now()->month) == $n)>{{ $lbl }} ({{ $n }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="text-xs text-slate-500">Creates a Draft run. You can then trigger payroll computation, approve, post to GL, and generate the bank file from the run details page.</p>
        <div class="flex justify-end gap-2 pt-3 border-t border-[var(--line)]">
            <a href="{{ route('payroll.runs.index') }}" class="tb-btn">Cancel</a>
            <button type="submit" class="tb-btn primary">Create Run</button>
        </div>
    </form>
</div>
@endsection
