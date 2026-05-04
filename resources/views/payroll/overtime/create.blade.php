@extends('layouts.app')
@section('title', 'Add Overtime')
@section('content')
<div class="p-4 max-w-2xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        Payroll /
        <a href="{{ route('overtime-sheet') }}" class="hover:underline">Overtime Sheet</a> /
        <span class="text-slate-900 font-semibold">Add Overtime</span>
    </div>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card">
        <div class="px-4 py-3 border-b border-[var(--line)] bg-slate-50 rounded-t-lg flex items-center gap-2">
            <span class="text-lg">📝</span>
            <h1 class="text-lg font-bold">Add Overtime</h1>
        </div>

        <form method="POST" action="{{ route('overtime-sheet.store') }}" class="p-5">
            @csrf

            <div class="grid grid-cols-12 gap-4 items-center mb-3">
                <label class="col-span-3 text-sm font-semibold text-slate-700">Month &amp; Year :</label>
                <div class="col-span-9 flex gap-2">
                    <select name="period_month" class="border border-[var(--line)] rounded p-2 text-sm">
                        @foreach([1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'] as $n=>$lbl)
                            <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="period_year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:110px">
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 items-center mb-3">
                <label class="col-span-3 text-sm font-semibold text-slate-700">Employee :</label>
                <div class="col-span-9">
                    <select name="emp_id" required class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                        <option value="">— Select Employee —</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->emp_id }}" @selected((int)$empId === (int)$e->emp_id)>{{ $e->emp_id }} — {{ $e->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 items-center mb-3">
                <label class="col-span-3 text-sm font-semibold text-slate-700">Overtime Rate :</label>
                <div class="col-span-9 flex items-center gap-2">
                    <input type="number" name="ot_rate" value="{{ old('ot_rate', $existing->ot_rate ?? 2) }}" step="0.25" min="0" max="5" required
                           class="border border-[var(--line)] rounded p-2 text-sm" style="width:100px">
                    <span class="text-xs text-slate-500">× normal hourly wage (typical: 1.5× weekday, 2× holiday)</span>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 items-center mb-3">
                <label class="col-span-3 text-sm font-semibold text-slate-700">Overtime Hours :</label>
                <div class="col-span-9">
                    <input type="number" name="ot_hours" value="{{ old('ot_hours', $existing->ot_hours ?? '') }}" step="0.25" min="0" max="744" required
                           class="border border-[var(--line)] rounded p-2 text-sm" style="width:140px">
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 items-start mb-3">
                <label class="col-span-3 text-sm font-semibold text-slate-700">Notes :</label>
                <div class="col-span-9">
                    <input type="text" name="notes" value="{{ old('notes', $existing->notes ?? '') }}" maxlength="500" placeholder="Optional — e.g. Diwali production push"
                           class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                </div>
            </div>

            {{-- Live preview matching SUGAM HR's Incentive (Manual) breakdown --}}
            <div class="rounded-lg bg-slate-50 border border-[var(--line)] p-3 text-xs mb-3" id="otPreview">
                <strong>Live preview:</strong>
                <table class="text-xs mt-2" style="width:100%">
                    <tr><td>Hourly wage (Gross ÷ 30 ÷ 8):</td><td class="text-right">₹<span id="prevHourlyBase">0.00</span></td></tr>
                    <tr><td>OT Rate (Hourly × Multiplier):</td><td class="text-right">₹<span id="prevHourlyRate">0.00</span> /hr</td></tr>
                    <tr><td>OT Amount (OT Rate × Hours):</td><td class="text-right font-bold">₹<span id="prevAmount">0.00</span></td></tr>
                    <tr><td>ESI on OT:</td><td class="text-right text-slate-400">— not deducted —</td></tr>
                    <tr style="border-top:1px solid #e2e8f0"><td><strong>Payable (full OT amount):</strong></td><td class="text-right text-green-700"><strong>₹<span id="prevPayable">0.00</span></strong></td></tr>
                </table>
                <p class="mt-2 text-slate-500">Saves to <code>overtime_records</code>; gross_earnings on the payslip auto-includes this OT when payroll runs.</p>
            </div>

            <script>
                const HOURLY = @json($hourlyByEmp);
                const ESI_ELIG = @json($esiEligibleByEmp);
                function recalc() {
                    const empSel = document.querySelector('select[name=emp_id]');
                    const empId  = empSel ? parseInt(empSel.value) : 0;
                    const rate   = parseFloat(document.querySelector('input[name=ot_rate]').value)  || 0;
                    const hours  = parseFloat(document.querySelector('input[name=ot_hours]').value) || 0;
                    const hourly = HOURLY[empId] || 0;
                    const hr     = +(hourly * rate).toFixed(2);
                    const amt    = +(hr * hours).toFixed(2);
                    document.getElementById('prevHourlyBase').textContent = hourly.toFixed(2);
                    document.getElementById('prevHourlyRate').textContent = hr.toFixed(2);
                    document.getElementById('prevAmount').textContent     = amt.toFixed(2);
                    document.getElementById('prevPayable').textContent    = amt.toFixed(2); // OT paid in full, no ESI deduction
                }
                document.querySelectorAll('select[name=emp_id], input[name=ot_rate], input[name=ot_hours]').forEach(el => {
                    el.addEventListener('input', recalc);
                    el.addEventListener('change', recalc);
                });
                recalc();
            </script>

            <div class="flex justify-start gap-2 pt-3 border-t border-[var(--line)]">
                <button type="submit" class="tb-btn primary" style="background:#16A34A;border-color:#15803D">💾 Save</button>
                <a href="{{ route('overtime-sheet', ['year'=>$year,'month'=>$month]) }}" class="tb-btn" style="background:#DC2626;color:#fff;border-color:#B91C1C">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
