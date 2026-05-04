@extends('layouts.app')
@section('title', 'Complete Salary Sheet')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">Complete Salary Sheet</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Complete Salary Sheet &mdash; {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('reports.complete-salary', ['year'=>$year, 'month'=>$month, 'export'=>'csv']) }}"
               class="tb-btn primary" style="background:#16A34A;border-color:#15803D">⬇ Export CSV</a>
            <a href="{{ route('payroll.runs.index') }}" class="tb-btn">View Runs</a>
        </div>
    </div>

    <form method="GET" class="card p-3 mb-4 flex gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
            <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                    <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                @endforeach
            </select></div>
        <button type="submit" class="tb-btn primary">Apply</button>
    </form>

    <p class="text-xs text-slate-500 mb-3">Each column header shows the calculation rule. Scroll horizontally to see all 30+ fields. Faded rows = no payslip generated for this period.</p>

    <div class="card overflow-x-auto">
        <table class="grid-tbl text-xs" style="min-width:3200px">
            <thead>
                <tr style="background:#FEF2F2">
                    <th colspan="5" style="background:#0F172A;color:white">IDENTITY</th>
                    <th colspan="9" style="background:#15803D;color:white">EARNINGS  (each prorated by attendance)</th>
                    <th colspan="8" style="background:#B91C1C;color:white">DEDUCTIONS (CoSS 2020 rules)</th>
                    <th colspan="2" style="background:#7C3AED;color:white">NET</th>
                    <th colspan="6" style="background:#0E7490;color:white">EMPLOYER COST (paid to govt on top)</th>
                    <th colspan="2" style="background:#A16207;color:white">CTC</th>
                </tr>
                <tr>
                    <th title="Employee identifier">Emp ID</th>
                    <th title="Employee full name">Name</th>
                    <th title="Salary group">Group</th>
                    <th title="Department">Dept</th>
                    <th title="Type: Staff / Sub-Staff / Worker">Type</th>

                    <th title="50% of Gross structure × attendance ratio">Basic<br><span class="text-[10px] font-normal opacity-70">stored</span></th>
                    <th title="DA = 5% of Gross structure × attendance ratio">DA<br><span class="text-[10px] font-normal opacity-70">stored</span></th>
                    <th title="HRA = 25% of Gross × attendance ratio">HRA<br><span class="text-[10px] font-normal opacity-70">stored</span></th>
                    <th title="Conveyance = 4% of Gross">Conv<br><span class="text-[10px] font-normal opacity-70">stored</span></th>
                    <th title="Medical = 2.5% of Gross">Med<br><span class="text-[10px] font-normal opacity-70">stored</span></th>
                    <th title="Special Allowance + remaining HRA component">Spl<br><span class="text-[10px] font-normal opacity-70">remainder</span></th>
                    <th title="Statutory bonus provision (not paid in monthly cycle)">Bonus<br><span class="text-[10px] font-normal opacity-70">8.33% × min(Basic+DA, 7k)</span></th>
                    <th title="Salary arrear from prior period revisions">Arrear<br><span class="text-[10px] font-normal opacity-70">manual</span></th>
                    <th title="Overtime amount">OT<br><span class="text-[10px] font-normal opacity-70">manual</span></th>

                    <th title="Sum of Basic + DA + HRA + Conv + Med + Spl + Bonus + Arrear + OT">Gross<br><span class="text-[10px] font-normal opacity-70">SUM earnings</span></th>

                    <th title="Employee EPF = 12% of min(Basic+DA, ₹15,000)">EPF<br><span class="text-[10px] font-normal opacity-70">12% of capped wages</span></th>
                    <th title="ESI Wages = Basic+DA + add-back. ESI Employee = 0.75% of wages (if wages ≤ ₹21k)">ESI<br><span class="text-[10px] font-normal opacity-70">0.75% × CoSS wages</span></th>
                    <th title="Profession Tax — state-slab. RJ = ₹0">PT<br><span class="text-[10px] font-normal opacity-70">state slab</span></th>
                    <th title="Labour Welfare Fund — state-specific, half-yearly in MH/Dec">LWF<br><span class="text-[10px] font-normal opacity-70">state freq</span></th>
                    <th title="Income Tax / TDS = annual_tax(slabs) ÷ 12">TDS<br><span class="text-[10px] font-normal opacity-70">§192 slabs ÷ 12</span></th>
                    <th title="Loan EMI deducted this period">Loan EMI<br><span class="text-[10px] font-normal opacity-70">manual</span></th>
                    <th title="Advance recovery">Advance<br><span class="text-[10px] font-normal opacity-70">manual</span></th>
                    <th title="Fine / penalty">Fine<br><span class="text-[10px] font-normal opacity-70">manual</span></th>

                    <th title="Sum of all deductions">Total Ded<br><span class="text-[10px] font-normal opacity-70">SUM ded</span></th>
                    <th title="Gross − Total Deductions">Net Pay<br><span class="text-[10px] font-normal opacity-70">GROSS − DED</span></th>

                    <th title="Employer EPF = 3.67% of wages (capped ₹15k)">ER PF<br><span class="text-[10px] font-normal opacity-70">3.67%</span></th>
                    <th title="EPS = 8.33% of capped wages">EPS<br><span class="text-[10px] font-normal opacity-70">8.33%</span></th>
                    <th title="EDLI = 0.5% of capped wages">EDLI<br><span class="text-[10px] font-normal opacity-70">0.5%</span></th>
                    <th title="EPF Admin charges = 0.5% of capped wages">Admin<br><span class="text-[10px] font-normal opacity-70">0.5%</span></th>
                    <th title="Employer ESI = 3.25% of CoSS wages (if eligible)">ER ESI<br><span class="text-[10px] font-normal opacity-70">3.25%</span></th>
                    <th title="Gratuity provision = 4.81% of (Basic+DA)">Gratuity<br><span class="text-[10px] font-normal opacity-70">4.81%</span></th>

                    <th title="Total cost to company = Gross + Employer PF + EPS + EDLI + Admin + Employer ESI + Gratuity provision">CTC<br><span class="text-[10px] font-normal opacity-70">company cost</span></th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    @php $e = $r['emp']; $p = $r['payslip']; @endphp
                    <tr class="@if(!$r['has']) opacity-50 @endif">
                        <td>{{ $e->emp_id }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td class="text-[11px]">{{ $e->salary_group->salary_group_name ?? '—' }}</td>
                        <td>{{ $e->department->dept_name ?? '—' }}</td>
                        <td><span class="pill pill-info">{{ $e->employee_type ?? '—' }}</span></td>

                        <td>{{ $p ? number_format($p->basic, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->da, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->hra, 0) : '—' }}</td>
                        <td>{{ $p ? number_format(is_numeric($p->conveyance) ? $p->conveyance : 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format(is_numeric($p->medical) ? $p->medical : 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->spl_allow ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->bonus ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->arrear ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->ot_amount ?? 0, 0) : '—' }}</td>

                        <td><strong>{{ $p ? number_format($p->gross_earnings ?? 0, 0) : '—' }}</strong></td>

                        <td>{{ $p ? number_format($p->epf_emp ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->esi_emp ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->pt ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->lwf_emp ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->tds ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->loan_emi ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->advance_recovery ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->fine_recovery ?? 0, 0) : '—' }}</td>

                        <td><strong>{{ $p ? number_format($p->total_deductions ?? 0, 0) : '—' }}</strong></td>
                        <td><strong style="color:var(--brand)">{{ $p ? number_format($p->net_pay ?? 0, 0) : '—' }}</strong></td>

                        <td>{{ $p ? number_format($p->employer_pf ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->eps ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->edli ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->pf_admin ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->employer_esi ?? 0, 0) : '—' }}</td>
                        <td>{{ $p ? number_format($p->gratuity_provision ?? 0, 0) : '—' }}</td>

                        <td><strong>{{ $p ? number_format($p->total_employer_cost ?? 0, 0) : '—' }}</strong></td>
                        <td>
                            @if($p)
                                <span class="pill pill-ok">{{ $p->disbursement_status ?? 'Pending' }}</span>
                            @else
                                <span class="pill pill-warn">No payslip</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="32" class="text-center py-6 text-slate-500">No employees in the active company.</td></tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
                <tfoot>
                    <tr style="background:#0F172A;color:white;font-weight:bold">
                        <td colspan="5" class="text-right">TOTALS &raquo;</td>
                        <td>{{ number_format($totals['basic'], 0) }}</td>
                        <td>{{ number_format($totals['da'], 0) }}</td>
                        <td>{{ number_format($totals['hra'], 0) }}</td>
                        <td>{{ number_format($totals['conv'], 0) }}</td>
                        <td>{{ number_format($totals['med'], 0) }}</td>
                        <td>{{ number_format($totals['spl'], 0) }}</td>
                        <td>{{ number_format($totals['bonus'], 0) }}</td>
                        <td>{{ number_format($totals['arrear'], 0) }}</td>
                        <td>{{ number_format($totals['ot'], 0) }}</td>
                        <td>{{ number_format($totals['gross'], 0) }}</td>
                        <td>{{ number_format($totals['epf_emp'], 0) }}</td>
                        <td>{{ number_format($totals['esi_emp'], 0) }}</td>
                        <td>{{ number_format($totals['pt'], 0) }}</td>
                        <td>{{ number_format($totals['lwf_emp'], 0) }}</td>
                        <td>{{ number_format($totals['tds'], 0) }}</td>
                        <td>{{ number_format($totals['loan'], 0) }}</td>
                        <td>{{ number_format($totals['advance'], 0) }}</td>
                        <td>{{ number_format($totals['fine'], 0) }}</td>
                        <td>{{ number_format($totals['total_ded'], 0) }}</td>
                        <td style="color:#FCD34D">{{ number_format($totals['net'], 0) }}</td>
                        <td>{{ number_format($totals['epf_er'], 0) }}</td>
                        <td>{{ number_format($totals['eps'], 0) }}</td>
                        <td>{{ number_format($totals['edli'], 0) }}</td>
                        <td>{{ number_format($totals['admin'], 0) }}</td>
                        <td>{{ number_format($totals['esi_er'], 0) }}</td>
                        <td>{{ number_format($totals['gratuity'], 0) }}</td>
                        <td style="color:#FCD34D">{{ number_format($totals['ctc'], 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- Formula reference card --}}
    <div class="card p-4 mt-4 text-xs">
        <div class="font-bold mb-2 text-sm">Formula reference (CoSS 2020)</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-slate-700">
            <div>📌 <strong>Gross</strong> = Basic + DA + HRA + Conv + Med + Spl + Bonus + Arrear + OT  (each × attendance ratio)</div>
            <div>📌 <strong>EPF (employee)</strong> = 12% × min(Basic + DA, ₹15,000)</div>
            <div>📌 <strong>ESI Wages</strong> = Basic + DA + max(0, Excluded − 50% × TR)  &nbsp; <em>(CoSS §2(88))</em></div>
            <div>📌 <strong>ESI (employee)</strong> = 0.75% × ESI Wages, only if Wages ≤ ₹21,000</div>
            <div>📌 <strong>PT</strong> = State-slab lookup. RJ has no PT.</div>
            <div>📌 <strong>LWF</strong> = State-specific. RJ has no LWF. MH ₹25 in Jun & Dec only.</div>
            <div>📌 <strong>TDS</strong> = AnnualTax(Gross × 12, regime) ÷ 12   &nbsp; <em>(§192 slabs, std. ded ₹75k new / ₹50k old, §87A rebate)</em></div>
            <div>📌 <strong>Total Deductions</strong> = EPF + ESI + PT + LWF + TDS + LoanEMI + Advance + Fine</div>
            <div>📌 <strong>Net Pay</strong> = Gross − Total Deductions</div>
            <div>📌 <strong>Employer PF (3.67%) + EPS (8.33%)</strong> = 12% × min(Basic+DA, ₹15k)</div>
            <div>📌 <strong>EDLI / Admin</strong> = 0.5% each × min(Basic+DA, ₹15k)</div>
            <div>📌 <strong>Employer ESI</strong> = 3.25% × ESI Wages</div>
            <div>📌 <strong>Gratuity Provision</strong> = 4.81% × (Basic+DA)  &nbsp; <em>(monthly accrual, paid on exit if 5+ yrs service)</em></div>
            <div>📌 <strong>CTC</strong> = Gross + Employer PF + EPS + EDLI + Admin + Employer ESI + Gratuity provision</div>
        </div>
    </div>
</div>
@endsection
