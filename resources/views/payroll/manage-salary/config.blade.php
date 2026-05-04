@extends('layouts.app')
@section('title', 'Employee Salary Configuration — '.$emp->full_name)
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">
        Payroll /
        <a href="{{ route('manage-salary.index') }}" class="hover:underline">Manage Salary</a> /
        <span class="text-slate-900 font-semibold">{{ $emp->full_name }} ({{ $emp->emp_id }})</span>
    </div>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    {{-- ── Downloads panel: per-employee payslip + salary sheet for any period ── --}}
    <div class="card mb-3" id="downloadsCard">
        <div class="px-4 py-2 border-b border-[var(--line)] bg-blue-50 rounded-t-lg flex items-center gap-2">
            <span class="text-lg">📥</span>
            <h2 class="text-base font-bold text-blue-900">Downloads — {{ $emp->full_name }}</h2>
            @if($payslipPeriods->isNotEmpty())
                <span class="ml-auto text-xs text-slate-600">{{ $payslipPeriods->count() }} payslip(s) generated</span>
            @endif
        </div>

        <div class="p-4">
            @if($payslipPeriods->isEmpty())
                <p class="text-sm text-slate-500">No payslips generated yet. Once you run salary for any period, download links will appear here.</p>
            @else
                {{-- Quick picker — select any period and download both --}}
                <div class="bg-slate-50 border border-[var(--line)] rounded p-3 mb-4">
                    <div class="flex flex-wrap gap-3 items-end">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Period :</label>
                            <select id="periodPicker" class="border border-[var(--line)] rounded p-2 text-sm" style="min-width:160px">
                                @foreach($payslipPeriods as $p)
                                    @php $monthName = \DateTime::createFromFormat('!m', $p->period_month)->format('F'); @endphp
                                    <option value="{{ $p->period_year }}/{{ $p->period_month }}">{{ $monthName }} {{ $p->period_year }} — Net ₹{{ number_format((float)$p->net_pay, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" onclick="goPayslip()" class="tb-btn primary" style="background:#0EA5E9;border-color:#0284C7">📄 View Payslip</button>
                        <button type="button" onclick="goPayslipPrint()" class="tb-btn primary" style="background:#DC2626;border-color:#B91C1C">⬇ Download Payslip PDF</button>
                        <button type="button" onclick="goSalarySheet()" class="tb-btn primary" style="background:#16A34A;border-color:#15803D">⬇ Download Salary Sheet (this employee)</button>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        💡 The payslip is the standard Indian-format slip (earnings/deductions/net pay).
                        The salary sheet is the SUGAM-style "Payment of Wages Register" — useful for the bank file or audit.
                    </p>
                </div>

                {{-- Full table of all generated periods with per-row downloads --}}
                <div class="overflow-x-auto">
                    <table class="grid-tbl text-xs">
                        <thead><tr>
                            <th>Period</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Net Pay</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr></thead>
                        <tbody>
                            @foreach($payslipPeriods as $p)
                                @php $monthName = \DateTime::createFromFormat('!m', $p->period_month)->format('F'); @endphp
                                <tr>
                                    <td>{{ $monthName }} {{ $p->period_year }}</td>
                                    <td class="text-right">₹{{ number_format((float)$p->gross_earnings, 2) }}</td>
                                    <td class="text-right text-green-700"><strong>₹{{ number_format((float)$p->net_pay, 2) }}</strong></td>
                                    <td>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold
                                            @if($p->disbursement_status === 'Paid') bg-green-100 text-green-800
                                            @else bg-amber-100 text-amber-800 @endif">
                                            {{ $p->disbursement_status ?? 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="flex gap-1">
                                        <a href="{{ route('payroll.payslips.show', [$emp->emp_id, $p->period_year, $p->period_month]) }}"
                                           class="tb-btn" style="padding:2px 8px;font-size:11px;background:#0EA5E9;color:#fff;border-color:#0284C7">View</a>
                                        <a href="{{ route('payroll.payslips.print', [$emp->emp_id, $p->period_year, $p->period_month]) }}" target="_blank"
                                           class="tb-btn" style="padding:2px 8px;font-size:11px;background:#DC2626;color:#fff;border-color:#B91C1C">⬇ Payslip PDF</a>
                                        <a href="{{ route('payroll.generate', ['company_id'=>$emp->company_id,'salary_group_id'=>$emp->salary_group_id,'year'=>$p->period_year,'month'=>$p->period_month,'get_list'=>1,'export'=>'pdf']) }}" target="_blank"
                                           class="tb-btn" style="padding:2px 8px;font-size:11px;background:#16A34A;color:#fff;border-color:#15803D">⬇ Sheet PDF</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
        function goPayslip() {
            const v = document.getElementById('periodPicker').value.split('/');
            window.location = `/payroll/payslips/{{ $emp->emp_id }}/${v[0]}/${v[1]}`;
        }
        function goPayslipPrint() {
            const v = document.getElementById('periodPicker').value.split('/');
            window.open(`/payroll/payslips/{{ $emp->emp_id }}/${v[0]}/${v[1]}/print`, '_blank');
        }
        function goSalarySheet() {
            const v = document.getElementById('periodPicker').value.split('/');
            window.open(`/payroll/generate?company_id={{ $emp->company_id }}&salary_group_id={{ $emp->salary_group_id }}&year=${v[0]}&month=${v[1]}&get_list=1&export=pdf`, '_blank');
        }
    </script>

    <div class="card">
        <div class="px-4 py-2 border-b border-[var(--line)] bg-slate-50 rounded-t-lg flex items-center gap-2">
            <span class="text-lg">💳</span>
            <h1 class="text-lg font-bold">Employee Salary Configuration</h1>
        </div>

        <form method="POST" action="{{ route('manage-salary.save', $emp->emp_id) }}" class="p-4" id="salaryConfigForm">
            @csrf

            {{-- Top section: identifiers + flags --}}
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-9">
                    {{-- Row 1 --}}
                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">E.Code :</label>
                            <input type="text" value="{{ $emp->emp_id }}" disabled class="block w-full border border-[var(--line)] rounded p-2 text-sm bg-slate-100">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Employee :</label>
                            <input type="text" value="{{ $emp->full_name }}" disabled class="block w-full border border-[var(--line)] rounded p-2 text-sm bg-slate-100">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Salary Group :</label>
                            <select name="salary_group_id" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                                @foreach($salaryGroups as $g)
                                    <option value="{{ $g->salary_group_id }}" @selected(old('salary_group_id', $emp->salary_group_id) == $g->salary_group_id)>{{ $g->salary_group_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: PF, FPF, ESI flags + numbers --}}
                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="flex items-center gap-2 text-sm mb-1"><input type="checkbox" name="pf_applicable_flag" value="1" @checked(old('pf_applicable_flag', $emp->pf_applicable_flag ?? true))> <span>PF Applicable</span></label>
                            <input type="text" name="epf_member_id" value="{{ old('epf_member_id', $emp->epf_member_id) }}" placeholder="PF No." class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm mb-1"><input type="checkbox" name="fpf_applicable_flag" value="1" @checked(old('fpf_applicable_flag', $emp->fpf_applicable_flag ?? true))> <span>FPF Applicable</span></label>
                            <input type="number" step="0.01" min="0" name="vpf_amount" value="{{ old('vpf_amount') }}" placeholder="VPF (optional)" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                            <p class="text-[10px] text-slate-500 mt-1">VPF is also editable per-period in Salary Deductions form.</p>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm mb-1"><input type="checkbox" name="esi_applicable_flag" value="1" @checked(old('esi_applicable_flag', $emp->esi_applicable_flag ?? true))> <span>ESI Applicable</span></label>
                            <input type="text" name="esi_ip_no" value="{{ old('esi_ip_no', $emp->esi_ip_no) }}" placeholder="ESI No." class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                        </div>
                    </div>

                    {{-- Row 3: C.O / OT flags + Overtime Rate --}}
                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="flex items-center gap-2 text-sm mb-1"><input type="checkbox" name="co_applicable_flag" value="1" @checked(old('co_applicable_flag', $emp->co_applicable_flag ?? false))> <span>C.O. Applicable</span></label>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm mb-1"><input type="checkbox" name="overtime_applicable_flag" value="1" @checked(old('overtime_applicable_flag', $emp->overtime_applicable_flag ?? false))> <span>Overtime Applicable</span></label>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Overtime Rate :</label>
                            <input type="number" step="0.25" min="0" max="5" name="overtime_rate" value="{{ old('overtime_rate', $emp->overtime_rate ?? 2) }}" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                        </div>
                    </div>

                    {{-- Row 4: WF, UAN, LTC, Gratuity --}}
                    <div class="grid grid-cols-4 gap-3 mb-3">
                        <div>
                            <label class="flex items-center gap-2 text-sm mb-1"><input type="checkbox" name="lwf_apply_flag" value="1" @checked(old('lwf_apply_flag', $emp->lwf_apply_flag ?? false))> <span>Apply Welfare Fund</span></label>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">UAN No :</label>
                            <input type="text" name="uan" value="{{ old('uan', $emp->uan) }}" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm mb-1"><input type="checkbox" name="ltc_entitled_flag" value="1" @checked(old('ltc_entitled_flag', $emp->ltc_entitled_flag ?? false))> <span>LTC Entitled</span></label>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Group Gratuity Code :</label>
                            <input type="text" name="group_gratuity_code" value="{{ old('group_gratuity_code', $emp->group_gratuity_code) }}" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                        </div>
                    </div>

                    {{-- Row 5: Payment Mode, A/C, Bank, IFSC --}}
                    <div class="grid grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode :</label>
                            <select name="payment_mode" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                                <option value="">— Select —</option>
                                @foreach(['Bank Transfer','NEFT','RTGS','IMPS','Cheque','Cash'] as $m)
                                    <option value="{{ $m }}" @selected(old('payment_mode', $emp->payment_mode) === $m)>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Salary A/c No :</label>
                            <input type="text" name="bank_account_no" value="{{ old('bank_account_no', $emp->bank_account_no) }}" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Bank Name :</label>
                            <select name="bank_id" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                                <option value="">— Select —</option>
                                @foreach($banks as $b)
                                    <option value="{{ $b->bank_id }}" @selected(old('bank_id', $emp->bank_id) == $b->bank_id)>{{ $b->bank_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">IFSC Code :</label>
                            <input type="text" name="bank_ifsc" value="{{ old('bank_ifsc', $emp->bank_ifsc) }}" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Auto Calculate side panel --}}
                <div class="col-span-3 bg-amber-50 border border-amber-200 rounded p-3 text-sm" style="background:#FFFBEB">
                    <label class="flex items-center gap-2 mb-3 font-semibold">
                        <input type="checkbox" name="auto_calc_flag" id="autoCalcFlag" value="1" @checked(old('auto_calc_flag', $emp->auto_calc_flag ?? true))> Auto Calculate
                    </label>
                    @php
                        $pcts = [
                            ['da_pct',        'DA',         $emp->da_pct        ?? 10],
                            ['hra_pct',       'HRA',        $emp->hra_pct       ?? 50],
                            ['conv_pct',      'Conveyance', $emp->conv_pct      ?? 8],
                            ['medical_pct',   'Medical',    $emp->medical_pct   ?? 5],
                            ['education_pct', 'Education',  $emp->education_pct ?? 5],
                        ];
                    @endphp
                    @foreach($pcts as [$name, $label, $val])
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-slate-700">{{ $label }} %</label>
                            <input type="number" name="{{ $name }}" step="0.01" min="0" max="100" value="{{ old($name, $val) }}" class="border border-[var(--line)] rounded p-1.5 text-sm text-right pct-input" style="width:80px">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Earnings section --}}
            <h3 class="text-sm font-bold mt-5 mb-2 pb-1 border-b border-[var(--line)]">Earnings (₹ per month)</h3>
            <div class="grid grid-cols-6 gap-3 mb-3">
                @php
                    $earnings = [
                        ['current_basic',  'Basic Salary', $emp->current_basic ?? 0],
                        ['current_da',     'DA',           $emp->current_da ?? 0],
                        ['current_hra',    'HRA',          $emp->current_hra ?? 0],
                        ['current_conv',   'Conveyance',   $emp->current_conv ?? 0],
                        ['current_med',    'Medical',      $emp->current_med ?? 0],
                        ['education_allow','Education',    $emp->education_allow ?? 0],
                    ];
                @endphp
                @foreach($earnings as [$name, $label, $val])
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $label }} :</label>
                        <input type="number" step="0.01" min="0" name="{{ $name }}" id="fld_{{ $name }}" value="{{ old($name, $val) }}" class="block w-full border border-[var(--line)] rounded p-2 text-sm earn-input" data-key="{{ $name }}">
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-6 gap-3 mb-3">
                @php
                    $extras = [
                        ['special_house_rent',    'Sp. House Rent',    $emp->special_house_rent ?? 0],
                        ['site_allowance',        'Site Allow',        $emp->site_allowance ?? 0],
                        ['sp_conv_petrol',        'Sp. Conv./Petrol',  $emp->sp_conv_petrol ?? 0],
                        ['other_allowance',       'Other Allow',       $emp->other_allowance ?? 0],
                        ['deputation_allowance',  'Deputation',        $emp->deputation_allowance ?? 0],
                        ['food_allowance',        'Food Allow',        $emp->food_allowance ?? 0],
                    ];
                @endphp
                @foreach($extras as [$name, $label, $val])
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $label }} :</label>
                        <input type="number" step="0.01" min="0" name="{{ $name }}" value="{{ old($name, $val) }}" class="block w-full border border-[var(--line)] rounded p-2 text-sm earn-input">
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-6 gap-3 mb-3">
                @php
                    $extras2 = [
                        ['city_allowance',    'City Allow',     $emp->city_allowance ?? 0],
                        ['voucher_cash_allow','Voucher/Cash',   $emp->voucher_cash_allow ?? 0],
                        ['kra_amount',        'KRA',            $emp->kra_amount ?? 0],
                        ['hard_duty_allow',   'Hard Duty Allow',$emp->hard_duty_allow ?? 0],
                    ];
                @endphp
                @foreach($extras2 as [$name, $label, $val])
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $label }} :</label>
                        <input type="number" step="0.01" min="0" name="{{ $name }}" value="{{ old($name, $val) }}" class="block w-full border border-[var(--line)] rounded p-2 text-sm earn-input">
                    </div>
                @endforeach

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Total Salary :</label>
                    <input type="text" id="totalSalary" value="{{ number_format((float)$emp->current_gross, 2) }}" disabled class="block w-full border border-[var(--line)] rounded p-2 text-sm bg-slate-100 text-right font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Gross Salary :</label>
                    <input type="text" id="grossSalary" value="{{ number_format((float)$emp->current_gross, 2) }}" disabled class="block w-full border border-[var(--line)] rounded p-2 text-sm bg-green-50 text-right font-bold text-green-700">
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="flex justify-start gap-2 pt-3 border-t border-[var(--line)] mt-4">
                <button type="submit" class="tb-btn primary" style="background:#16A34A;border-color:#15803D">💾 Save</button>
                <a href="{{ route('manage-salary.index') }}" class="tb-btn" style="background:#DC2626;color:#fff;border-color:#B91C1C">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function recalc() {
    const auto    = document.getElementById('autoCalcFlag').checked;
    const basic   = parseFloat(document.querySelector('input[name=current_basic]').value) || 0;

    if (auto && basic > 0) {
        const daPct  = parseFloat(document.querySelector('input[name=da_pct]').value)        || 0;
        const hraPct = parseFloat(document.querySelector('input[name=hra_pct]').value)       || 0;
        const cvPct  = parseFloat(document.querySelector('input[name=conv_pct]').value)      || 0;
        const mdPct  = parseFloat(document.querySelector('input[name=medical_pct]').value)   || 0;
        const edPct  = parseFloat(document.querySelector('input[name=education_pct]').value) || 0;
        document.querySelector('input[name=current_da]').value     = +(basic * daPct  / 100).toFixed(2);
        document.querySelector('input[name=current_hra]').value    = +(basic * hraPct / 100).toFixed(2);
        document.querySelector('input[name=current_conv]').value   = +(basic * cvPct  / 100).toFixed(2);
        document.querySelector('input[name=current_med]').value    = +(basic * mdPct  / 100).toFixed(2);
        document.querySelector('input[name=education_allow]').value= +(basic * edPct  / 100).toFixed(2);
    }

    let total = 0;
    document.querySelectorAll('.earn-input').forEach(i => total += parseFloat(i.value) || 0);
    document.getElementById('totalSalary').value = total.toFixed(2);
    document.getElementById('grossSalary').value = total.toFixed(2);
}

document.querySelectorAll('.earn-input, .pct-input').forEach(i => i.addEventListener('input', recalc));
document.getElementById('autoCalcFlag').addEventListener('change', recalc);
recalc();
</script>
@endsection
