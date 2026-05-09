@php
$basic = $p->basic ?? 0;
$hra   = $p->hra ?? 0;
$da    = $p->da ?? 0;
$conv  = is_numeric($p->conveyance ?? 0) ? $p->conveyance : 0;
$med   = is_numeric($p->medical ?? 0) ? $p->medical : 0;
$spl   = $p->spl_allow ?? 0;
$bonus = $p->bonus ?? 0;
$arr   = $p->arrear ?? 0;
$gross = $p->gross_earnings ?? ($basic + $hra + $da + $conv + $med + $spl + $bonus + $arr);

$pf    = $p->epf_emp ?? 0;
$esi   = $p->esi_emp ?? 0;
$pt    = $p->pt ?? 0;
$lwf   = $p->lwf_emp ?? 0;
$tds   = $p->tds ?? 0;
$totDed= $pf + $esi + $pt + $lwf + $tds;
$net   = $p->net_pay ?? max(0, $gross - $totDed);
@endphp
<div class="card p-6 mb-6 print:shadow-none print:border-0">
    {{-- Header --}}
    <div class="border-b-2 border-[var(--brand)] pb-3 mb-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-lg font-bold text-[var(--brand)]">{{ $emp->company->company_name ?? 'Company' }}</div>
                <div class="text-xs text-slate-500">{{ $emp->company->registered_address_line1 ?? '' }}</div>
                <div class="text-xs text-slate-500">PAN: {{ $emp->company->pan ?? '—' }} • TAN: {{ $emp->company->tan ?? '—' }}</div>
            </div>
            <div class="text-right">
                <div class="font-bold">PAYSLIP</div>
                <div class="text-xs text-slate-500">{{ \DateTime::createFromFormat('!m', $p->period_month)->format('F') }} {{ $p->period_year }}</div>
            </div>
        </div>
    </div>

    {{-- Employee block --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-4">
        <div><div class="text-[11px] text-slate-500 uppercase">Emp ID</div><div class="font-semibold">{{ $emp->emp_id }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">Name</div><div class="font-semibold">{{ $emp->full_name }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">Designation</div><div class="font-semibold">{{ $emp->designation->designation_name ?? '—' }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">Department</div><div class="font-semibold">{{ $emp->department->dept_name ?? '—' }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">PAN</div><div class="font-semibold">{{ $emp->pan_no ?? '—' }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">UAN</div><div class="font-semibold">{{ $emp->uan ?? '—' }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">PF Member ID</div><div class="font-semibold">{{ $emp->epf_member_id ?? '—' }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">ESI IP No</div><div class="font-semibold">{{ $emp->esi_ip_no ?? '—' }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">DOJ</div><div class="font-semibold">{{ $emp->date_of_joining }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">Bank A/c</div><div class="font-semibold">{{ $emp->bank_account_no ?? '—' }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">IFSC</div><div class="font-semibold">{{ $emp->bank_ifsc ?? '—' }}</div></div>
        <div><div class="text-[11px] text-slate-500 uppercase">Days Worked</div><div class="font-semibold">{{ $p->present_days ?? '—' }} / {{ $p->payable_days ?? '—' }}</div></div>
    </div>

    {{-- Earnings & Deductions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <div class="font-semibold text-sm mb-2 text-slate-700">EARNINGS</div>
            <table class="grid-tbl text-sm">
                <tr><td>Basic</td><td class="text-right">&#8377;{{ number_format($basic, 0) }}</td></tr>
                <tr><td>HRA (Section 10(13A))</td><td class="text-right">&#8377;{{ number_format($hra, 0) }}</td></tr>
                <tr><td>DA</td><td class="text-right">&#8377;{{ number_format($da, 0) }}</td></tr>
                <tr><td>Conveyance</td><td class="text-right">&#8377;{{ number_format($conv, 0) }}</td></tr>
                <tr><td>Medical</td><td class="text-right">&#8377;{{ number_format($med, 0) }}</td></tr>
                <tr><td>Special Allowance</td><td class="text-right">&#8377;{{ number_format($spl, 0) }}</td></tr>
                <tr><td>Bonus</td><td class="text-right">&#8377;{{ number_format($bonus, 0) }}</td></tr>
                <tr><td>Arrear</td><td class="text-right">&#8377;{{ number_format($arr, 0) }}</td></tr>
                <tr style="background:#FEF2F2"><th>Gross Earnings</th><th class="text-right">&#8377;{{ number_format($gross, 0) }}</th></tr>
            </table>
        </div>
        <div>
            <div class="font-semibold text-sm mb-2 text-slate-700">DEDUCTIONS</div>
            <table class="grid-tbl text-sm">
                <tr><td>EPF (12% of Basic+DA, capped &#8377;15k)</td><td class="text-right">&#8377;{{ number_format($pf, 0) }}</td></tr>
                <tr><td>ESI (0.75% of gross, if eligible)</td><td class="text-right">&#8377;{{ number_format($esi, 0) }}</td></tr>
                <tr><td>Professional Tax</td><td class="text-right">&#8377;{{ number_format($pt, 0) }}</td></tr>
                <tr><td>LWF</td><td class="text-right">&#8377;{{ number_format($lwf, 0) }}</td></tr>
                <tr><td>TDS (Income Tax)</td><td class="text-right">&#8377;{{ number_format($tds, 0) }}</td></tr>
                <tr style="background:#FEF2F2"><th>Total Deductions</th><th class="text-right">&#8377;{{ number_format($totDed, 0) }}</th></tr>
            </table>
        </div>
    </div>

    {{-- Net Pay --}}
    <div class="mt-4 p-4 rounded-lg" style="background:linear-gradient(135deg,#B91C1C,#7F1D1D);color:white">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs uppercase opacity-80">Net Pay</div>
                <div class="text-2xl font-bold">&#8377;{{ number_format($net, 0) }}</div>
                <div class="text-xs opacity-80 mt-1">Indian Rupees {{ number_format($net, 0) }} only</div>
            </div>
            <div class="text-right text-xs opacity-80">
                <div>Salary Period: {{ \DateTime::createFromFormat('!m', $p->period_month)->format('M') }} {{ $p->period_year }}</div>
                <div>Pay Date: {{ now()->format('d-M-Y') }}</div>
            </div>
        </div>
    </div>

    <div class="text-[10px] text-slate-400 mt-3 text-center">
        This is a computer-generated payslip and does not require a signature. EPF, ESI, PT, LWF, TDS as per applicable Indian statutes.
    </div>
</div>
