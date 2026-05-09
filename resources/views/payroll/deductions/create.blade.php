@extends('layouts.app')
@section('title', 'Add Salary Deduction')
@section('content')
<div class="p-4 max-w-5xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        Payroll /
        <a href="{{ route('deductions.listing', ['year'=>$year,'month'=>$month]) }}" class="hover:underline">Salary Deductions</a> /
        <span class="text-slate-900 font-semibold">Add Salary Deduction</span>
    </div>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('status'))
        <div class="mb-3 rounded-lg bg-blue-50 border border-blue-200 px-3 py-2 text-sm text-blue-800">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="px-4 py-3 border-b border-[var(--line)] bg-slate-50 rounded-t-lg">
            <h1 class="text-lg font-bold flex items-center gap-2"><span>📝</span> Add Salary Deduction</h1>
        </div>

        <form method="POST" action="{{ route('deductions.store') }}" class="p-5">
            @csrf

            {{-- Period + Employee picker --}}
            <div class="grid grid-cols-12 gap-4 items-center mb-3 pb-3 border-b border-[var(--line)]">
                <label class="col-span-3 text-sm font-semibold text-slate-700">Month &amp; Year :</label>
                <div class="col-span-9 flex gap-2">
                    <select name="period_month" class="border border-[var(--line)] rounded p-2 text-sm">
                        @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                            <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="period_year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:100px"/>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 items-center mb-3">
                <label class="col-span-3 text-sm font-semibold text-slate-700">Employee Name :</label>
                <div class="col-span-9 flex gap-2 items-center">
                    <select name="emp_id" required id="empPicker" class="border border-[var(--line)] rounded p-2 text-sm" style="min-width:340px">
                        <option value="">-- Select --</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->emp_id }}" @selected((int)$empId === (int)$e->emp_id)>{{ $e->emp_id }} — {{ $e->full_name }}</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="reloadForEmp()" class="tb-btn">🔍 Load existing</button>
                    @if($payslip)
                        <span class="text-xs text-slate-500 ml-2">
                            Gross: <strong>₹{{ number_format($payslip->gross_earnings, 0) }}</strong> ·
                            TDS (manual): <strong>₹{{ number_format($payslip->tds ?? 0, 0) }}</strong>
                        </span>
                    @endif
                </div>
            </div>

            {{-- Deduction line items --}}
            @php
                $rows = [
                    ['advance_deduction',  'Advance Deduction'],
                    ['loan_deduction',     'Loan Deduction'],
                    ['ag_donation',        'A.G. Donation'],
                    ['maintenance_charge', 'Maintenance Charge'],
                    ['mobile_deduction',   'Mobile Deduction'],
                    ['canteen_deduction',  'Canteen Deduction'],
                    ['tds_deduction',      'TDS Deduction'],
                    ['vpf_deduction',      'VPF (Voluntary PF)'],
                    ['incentive_hours',    'Incentive Hours'],
                    ['misc_deduction',     'Miscellaneous Deduction'],
                    ['rent_meridian',      'Rent (Meridian)'],
                ];
            @endphp

            @foreach($rows as [$name, $label])
                <div class="grid grid-cols-12 gap-4 items-center mb-2 py-1.5 hover:bg-slate-50 rounded">
                    <label class="col-span-3 text-sm font-semibold text-slate-700">{{ $label }} :</label>
                    <div class="col-span-9">
                        <input type="number" name="{{ $name }}" step="0.01" min="0"
                               value="{{ old($name, $existing->{$name} ?? 0) }}"
                               class="border border-[var(--line)] rounded p-1.5 text-sm" style="width:140px"
                               @if($name === 'tds_deduction') id="tdsInput" @endif>
                        @if($name === 'tds_deduction')
                            <span class="ml-3 text-xs text-slate-500 italic">
                                TDS is applied <strong>only</strong> when entered here. Leave blank/0 to deduct nothing.
                            </span>
                            <input type="hidden" name="tds_override_flag" value="1">
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="grid grid-cols-12 gap-4 items-start mt-3 pt-3 border-t border-[var(--line)]">
                <label class="col-span-3 text-sm font-semibold text-slate-700">Deduction Remarks :</label>
                <div class="col-span-9">
                    <input type="text" name="remarks" value="{{ old('remarks', $existing->remarks ?? '') }}"
                           class="block w-full border border-[var(--line)] rounded p-2 text-sm" maxlength="1000"
                           placeholder="e.g. Mobile bill of Apr-26, advance taken on 12 Apr">
                </div>
            </div>

            <div class="flex justify-start gap-2 pt-4 mt-4 border-t border-[var(--line)]">
                <button type="submit" class="tb-btn primary" style="background:#16A34A;border-color:#15803D">💾 Save</button>
                <a href="{{ route('deductions.listing', ['year'=>$year,'month'=>$month]) }}" class="tb-btn" style="background:#DC2626;color:#fff;border-color:#B91C1C">Cancel</a>
            </div>

            @if($payslip)
                <div class="mt-4 p-3 bg-slate-50 rounded text-xs">
                    <strong>Live preview after save (auto-recalculates net pay):</strong>
                    <table class="text-xs mt-2" style="width:100%">
                        <tr><td>Gross Earnings:</td><td class="text-right">₹{{ number_format($payslip->gross_earnings, 0) }}</td></tr>
                        <tr><td>EPF + ESI + PT + LWF (statutory):</td><td class="text-right">₹{{ number_format(($payslip->epf_emp ?? 0)+($payslip->esi_emp ?? 0)+(float)($payslip->pt ?? 0)+($payslip->lwf_emp ?? 0), 0) }}</td></tr>
                        <tr><td>Current TDS (manual only):</td><td class="text-right">₹{{ number_format($payslip->tds ?? 0, 0) }}</td></tr>
                        <tr><td>Current Total Deductions:</td><td class="text-right">₹{{ number_format($payslip->total_deductions ?? 0, 0) }}</td></tr>
                        <tr><td><strong>Current Net Pay:</strong></td><td class="text-right"><strong>₹{{ number_format($payslip->net_pay ?? 0, 0) }}</strong></td></tr>
                    </table>
                </div>
            @endif
        </form>
    </div>
</div>

<script>
function reloadForEmp() {
    const emp = document.getElementById('empPicker').value;
    if (!emp) { alert('Pick an employee first'); return; }
    const m = document.querySelector('select[name=period_month]').value;
    const y = document.querySelector('input[name=period_year]').value;
    window.location = `{{ route('deductions.create') }}?emp_id=${emp}&year=${y}&month=${m}`;
}
</script>
@endsection
