<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PF ECR — {{ $monthName }} {{ $year }} — {{ $company->company_name ?? 'Company' }}</title>
    @include('statutory._pdf_styles')
    <style>
        /* PF ECR is a wide 30-column data sheet — set tighter typography. */
        @page { size: A3 landscape; margin: 6mm; }
        table.ecr { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        table.ecr th, table.ecr td { border: 1px solid #555; padding: 2px 3px; vertical-align: middle; }
        table.ecr thead th { background: #E5E7EB; font-weight: 700; font-size: 8px; text-align: center; line-height: 1.15; }
        table.ecr td.l { text-align: left; }
        table.ecr td.r { text-align: right; }
        table.ecr td.c { text-align: center; }
        table.ecr tr.totals td { background: #FEF3C7; font-weight: 700; }
    </style>
</head>
<body>

<div class="actions">
    <button onclick="window.print()">🖨 Save as PDF</button>
    <button class="secondary" onclick="window.close()">Close</button>
</div>

<div class="sheet">

    <div class="gov-header">
        <div class="top">
            <div>
                <h1>Employees' Provident Fund Organisation (EPFO)</h1>
                <h2>Electronic Challan-cum-Return (ECR) — Monthly Member Data</h2>
                <div style="font-size:10.5px;margin-top:2px">EPF &amp; MP Act 1952 — ECR Upload Format (30 columns)</div>
            </div>
            <div class="form-no">
                Form ECR<br><small>(System-generated)</small>
            </div>
        </div>
        <hr>
        <div class="meta">
            <div>
                <div><strong>Establishment Name:</strong> {{ $company->company_name ?? '—' }}</div>
                <div><strong>Establishment Code:</strong> {{ $company->epf_establishment_code ?? '—' }}</div>
                <div><strong>Address:</strong>
                    {{ trim(($company->registered_address_line1 ?? '') . ', ' . ($company->registered_address_line2 ?? ''), ', ') ?: '—' }}
                </div>
                <div><strong>PAN / TAN:</strong> {{ $company->pan ?? '—' }} / {{ $company->tan ?? '—' }}</div>
            </div>
            <div>
                <div><strong>Wage Month:</strong> {{ $monthName }} {{ $year }}</div>
                <div><strong>Salary Group:</strong> {{ $group->salary_group_name ?? 'All Groups' }}</div>
                <div><strong>Total Members:</strong> {{ $rows->count() }}</div>
                <div><strong>Generated On:</strong> {{ now()->format('d-M-Y H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- ============ EPFO ECR 30-column upload schema ============ --}}
    <table class="ecr">
        <thead>
            <tr>
                <th>PFNo</th>
                <th>UANNo</th>
                <th>Name</th>
                <th>Gross<br>Wages</th>
                <th>EPF</th>
                <th>EPS</th>
                <th>EDLI</th>
                <th>EPFC</th>
                <th>EPFR</th>
                <th>EPSC</th>
                <th>EPSR</th>
                <th>DIFC</th>
                <th>DIFR</th>
                <th>NCP</th>
                <th>RADV</th>
                <th>ARREPF</th>
                <th>ARREPFEE</th>
                <th>ARREPER</th>
                <th>ARREPS</th>
                <th>FNAME</th>
                <th>RMBR</th>
                <th>DOB</th>
                <th>GENDER</th>
                <th>DOJEPF</th>
                <th>DOJEPS</th>
                <th>DOEEPF</th>
                <th>DOEEPFS</th>
                <th>REASON</th>
                <th>Salary Group</th>
                <th>Company</th>
            </tr>
        </thead>
        <tbody>
            @php
                $empIds = $rows->pluck('emp_id')->all();
                $emps   = \App\Models\Employee::with(['salary_group','company'])
                    ->whereIn('emp_id', $empIds)->get()->keyBy('emp_id');
            @endphp
            @forelse($rows as $r)
                @php
                    $e        = $emps->get($r->emp_id);
                    $epfWage  = (float) ($r->epf_wage_capped ?? 0);
                    $epsWage  = (float) ($r->eps_wage_capped ?? 0);
                    $edliWage = (float) ($r->edli_wage_capped ?? 0);
                    $epfTot   = (float) ($r->ee_share_12pct ?? 0);
                    $epsTot   = (float) ($r->eps_8_33 ?? 0);
                    $erEpf    = (float) ($r->er_share_3_67 ?? 0);
                    $dojEpf   = $e && $e->epf_join_date     ? \Carbon\Carbon::parse($e->epf_join_date)->format('d/m/Y')
                              : ($e && $e->date_of_joining ? \Carbon\Carbon::parse($e->date_of_joining)->format('d/m/Y') : '');
                    $dojEps   = $e && $e->eps_join_date     ? \Carbon\Carbon::parse($e->eps_join_date)->format('d/m/Y') : $dojEpf;
                    $exit     = $e && $e->date_of_relieving ? \Carbon\Carbon::parse($e->date_of_relieving)->format('d/m/Y') : '';
                @endphp
                <tr>
                    <td class="l">{{ $r->member_id_pf ?? ($e->epf_member_id ?? '') }}</td>
                    <td class="l">{{ $r->uan ?? ($e->uan ?? '') }}</td>
                    <td class="l">{{ $r->member_name ?? ($e->full_name ?? '') }}</td>
                    <td class="r">{{ number_format((float) $r->gross_wage, 0) }}</td>
                    <td class="r">{{ number_format($epfWage, 0) }}</td>
                    <td class="r">{{ number_format($epsWage, 0) }}</td>
                    <td class="r">{{ number_format($edliWage, 0) }}</td>
                    <td class="r">{{ number_format($epfTot, 0) }}</td>
                    <td class="r">{{ number_format($epfTot, 0) }}</td>
                    <td class="r">{{ number_format($epsTot, 0) }}</td>
                    <td class="r">{{ number_format($epsTot, 0) }}</td>
                    <td class="r">{{ number_format($erEpf, 0) }}</td>
                    <td class="r">{{ number_format($erEpf, 0) }}</td>
                    <td class="c">{{ (int) ($r->ncp_days ?? 0) }}</td>
                    <td class="r">0</td>
                    <td class="r">0</td>
                    <td class="r">0</td>
                    <td class="r">0</td>
                    <td class="r">0</td>
                    <td class="l">{{ $e->fathers_name ?? '' }}</td>
                    <td class="c">—</td>
                    <td class="c">{{ $e && $e->dob ? \Carbon\Carbon::parse($e->dob)->format('d/m/Y') : '' }}</td>
                    <td class="c">{{ strtoupper(substr($e->gender ?? '', 0, 1)) }}</td>
                    <td class="c">{{ $dojEpf }}</td>
                    <td class="c">{{ $dojEps }}</td>
                    <td class="c">{{ $exit }}</td>
                    <td class="c">{{ $exit }}</td>
                    <td class="l">{{ $e->exit_reason ?? '' }}</td>
                    <td class="l">{{ $e->salary_group->salary_group_name ?? '' }}</td>
                    <td class="l">{{ $e->company->company_code ?? ($company->company_code ?? '') }}</td>
                </tr>
            @empty
                <tr><td colspan="30" class="c" style="padding:24px">No PF ECR rows for this period. Click "Generate ECR" first.</td></tr>
            @endforelse

            @if($rows->count())
                <tr class="totals">
                    <td colspan="3" class="c">TOTAL</td>
                    <td class="r">{{ number_format($rows->sum('gross_wage'), 0) }}</td>
                    <td class="r">{{ number_format($rows->sum('epf_wage_capped'), 0) }}</td>
                    <td class="r">{{ number_format($rows->sum('eps_wage_capped'), 0) }}</td>
                    <td class="r">{{ number_format($rows->sum('edli_wage_capped'), 0) }}</td>
                    <td class="r">{{ number_format($totals['ee'], 0) }}</td>
                    <td class="r">{{ number_format($totals['ee'], 0) }}</td>
                    <td class="r">{{ number_format($totals['eps_c'], 0) }}</td>
                    <td class="r">{{ number_format($totals['eps_c'], 0) }}</td>
                    <td class="r">{{ number_format($totals['er'], 0) }}</td>
                    <td class="r">{{ number_format($totals['er'], 0) }}</td>
                    <td colspan="16" class="c">—</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- ============ EPFO Challan Account Summary ============ --}}
    <div style="margin-top:14px; font-weight:700; font-size:12px">EPFO Challan Summary (Account-wise)</div>
    <table class="gov-table" style="margin-top:4px">
        <thead>
            <tr>
                <th style="width:90px">Account</th>
                <th style="width:220px">Description</th>
                <th style="width:90px">Rate (%)</th>
                <th style="width:120px">Wage Base (₹)</th>
                <th style="width:120px">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="c">A/C 1 (EPF)</td>
                <td>EE Contribution to EPF (12%) + ER Differential</td>
                <td class="c">12.00 (EE) + 3.67 (ER)</td>
                <td class="r">{{ number_format($totals['epf'], 0) }}</td>
                <td class="r">{{ number_format($totals['ee'] + $totals['er'], 0) }}</td>
            </tr>
            <tr>
                <td class="c">A/C 2 (Admin)</td>
                <td>EPF Administration Charges</td>
                <td class="c">0.50</td>
                <td class="r">{{ number_format($totals['epf'], 0) }}</td>
                <td class="r">{{ number_format($totals['admin'], 0) }}</td>
            </tr>
            <tr>
                <td class="c">A/C 10 (EPS)</td>
                <td>Employees' Pension Scheme</td>
                <td class="c">8.33</td>
                <td class="r">{{ number_format($totals['eps'], 0) }}</td>
                <td class="r">{{ number_format($totals['eps_c'], 0) }}</td>
            </tr>
            <tr>
                <td class="c">A/C 21 (EDLI)</td>
                <td>Employees Deposit-Linked Insurance</td>
                <td class="c">0.50</td>
                <td class="r">{{ number_format(min($totals['epf'], $rows->count() * 15000), 0) }}</td>
                <td class="r">{{ number_format($totals['edli'], 0) }}</td>
            </tr>
            <tr>
                <td class="c">A/C 22 (EDLI Admin)</td>
                <td>EDLI Inspection Charges (waived w.e.f. 01-Apr-2017)</td>
                <td class="c">0.00</td>
                <td class="r">—</td>
                <td class="r">0</td>
            </tr>
            <tr class="totals-row">
                <td class="label" colspan="4">TOTAL CHALLAN AMOUNT</td>
                <td class="r">₹ {{ number_format($totals['challan'], 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="declaration">
        <b>Declaration:</b> I/We hereby certify that the above particulars are true and correct, and that contributions have been
        deducted at prescribed rates from the wages of all members eligible under the Employees' Provident Funds &amp; Miscellaneous
        Provisions Act, 1952. Member-wise wages and contributions tally with the wage register of the establishment.
    </div>

    <div class="sigs">
        <div class="blk">
            <div class="lbl">Signature of Authorised Signatory</div>
            <div>{{ $company->authorized_signatory_name ?? '________________________' }}</div>
            <div style="font-size:10px; color:#555">{{ $company->authorized_signatory_designation ?? '' }}</div>
        </div>
        <div class="blk">
            <div class="lbl">Place &amp; Date</div>
            <div>______________________ &nbsp; {{ now()->format('d-M-Y') }}</div>
        </div>
        <div class="blk">
            <div class="lbl">Establishment Seal</div>
            <div style="height:36px"></div>
        </div>
    </div>

    <div class="footer">
        <div>Form ECR (EPFO Upload Format) — System generated by Hreasy by WebSenor on {{ now()->format('d-M-Y H:i') }}</div>
        <div>Page 1 of 1</div>
    </div>
</div>

</body>
</html>
