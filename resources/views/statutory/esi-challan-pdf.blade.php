<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ESI Monthly Contribution — {{ $monthName }} {{ $year }} — {{ $company->company_name ?? 'Company' }}</title>
    @include('statutory._pdf_styles')
    <style>
        table.esic { width: 100%; border-collapse: collapse; font-size: 11px; }
        table.esic th, table.esic td { border: 1px solid #555; padding: 4px 6px; vertical-align: middle; }
        table.esic thead th { background: #E5E7EB; font-weight: 700; font-size: 10.5px; text-align: center; }
        table.esic td.l { text-align: left; }
        table.esic td.r { text-align: right; }
        table.esic td.c { text-align: center; }
        table.esic tr.totals td { background: #FEF3C7; font-weight: 700; }
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
                <h1>Employees' State Insurance Corporation (ESIC)</h1>
                <h2>Monthly Contribution Return — Upload Format</h2>
                <div style="font-size:10.5px;margin-top:2px">ESI Act 1948 §39 / ESI (General) Regulations 1950 — Reg. 31</div>
            </div>
            <div class="form-no">
                Monthly Return<br><small>(System-generated)</small>
            </div>
        </div>
        <hr>
        <div class="meta">
            <div>
                <div><strong>Employer Name:</strong> {{ $company->company_name ?? '—' }}</div>
                <div><strong>Employer Code (ESIC):</strong> {{ $company->esic_code ?? '—' }}</div>
                <div><strong>Address:</strong>
                    {{ trim(($company->registered_address_line1 ?? '') . ', ' . ($company->registered_address_line2 ?? ''), ', ') ?: '—' }}
                </div>
                <div><strong>PAN:</strong> {{ $company->pan ?? '—' }}</div>
            </div>
            <div>
                <div><strong>Wage Month:</strong> {{ $monthName }} {{ $year }}</div>
                <div><strong>Salary Group:</strong> {{ $group->salary_group_name ?? 'All Groups' }}</div>
                <div><strong>Total IPs:</strong> {{ $rows->count() }}</div>
                <div><strong>Generated On:</strong> {{ now()->format('d-M-Y H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- ESIC official upload schema (matches uploaded XLSX format) --}}
    <table class="esic">
        <thead>
            <tr>
                <th>IP Number</th>
                <th>IP Name</th>
                <th>Payble Days</th>
                <th>Total Monthly Wages</th>
                <th>Reason Code (Zero Working Days)</th>
                <th>Last Working Day</th>
                <th>S. Group</th>
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
                    $exitDate = ($e && !empty($e->date_of_relieving))
                        ? \Carbon\Carbon::parse($e->date_of_relieving)->format('d/m/Y') : '';
                @endphp
                <tr>
                    <td class="c">{{ $r->ip_no ?: ($e->esi_ip_no ?? '') }}</td>
                    <td class="l">{{ $r->member_name ?: ($e->full_name ?? '') }}</td>
                    <td class="c">{{ rtrim(rtrim(number_format((float) ($r->days_worked ?? 0), 2, '.', ''), '0'), '.') ?: '0' }}</td>
                    <td class="r">{{ number_format((float) ($r->gross_wage ?? 0), 0) }}</td>
                    <td class="c">-----</td>
                    <td class="c">{{ $exitDate }}</td>
                    <td class="l">{{ $e->salary_group->salary_group_name ?? '' }}</td>
                    <td class="l">{{ $e->company->company_code ?? ($company->company_code ?? '') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="c" style="padding:24px">No ESI rows for this period. Click "Generate ESI" first.</td></tr>
            @endforelse

            @if($rows->count())
                <tr class="totals">
                    <td colspan="3" class="c">TOTAL</td>
                    <td class="r">{{ number_format($totals['gross'], 0) }}</td>
                    <td colspan="4" class="c">—</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Challan summary block (employer signs against this) --}}
    <div class="summary-box" style="margin-top:14px">
        <div class="cell"><span class="lbl">Total IPs</span><span class="val">{{ $rows->count() }}</span></div>
        <div class="cell"><span class="lbl">Wage Bill (₹)</span><span class="val">{{ number_format($totals['gross'], 0) }}</span></div>
        <div class="cell"><span class="lbl">EE Share 0.75%</span><span class="val">₹ {{ number_format($totals['ee'], 0) }}</span></div>
        <div class="cell"><span class="lbl">ER Share 3.25%</span><span class="val">₹ {{ number_format($totals['er'], 0) }}</span></div>
        <div class="cell total"><span class="lbl">Total Contribution</span><span class="val">₹ {{ number_format($totals['total'], 0) }}</span></div>
        <div class="cell"><span class="lbl">Due Date</span><span class="val" style="font-size:11px">15-{{ \DateTime::createFromFormat('!m', $month % 12 + 1)->format('M') }}-{{ $month == 12 ? $year + 1 : $year }}</span></div>
    </div>

    <div class="declaration">
        <b>Declaration:</b> I/We hereby certify that the above details of insured persons, paybble days, and wages for the
        contribution period are true and correct. Contributions have been computed under Sec. 39 of the ESI Act, 1948 and
        Regulation 31 of ESI (General) Regulations, 1950 and will be remitted on or before the prescribed due date.
    </div>

    <div class="sigs">
        <div class="blk">
            <div class="lbl">Signature of Employer / Authorised Signatory</div>
            <div>{{ $company->authorized_signatory_name ?? '________________________' }}</div>
            <div style="font-size:10px; color:#555">{{ $company->authorized_signatory_designation ?? '' }}</div>
        </div>
        <div class="blk">
            <div class="lbl">Place &amp; Date</div>
            <div>______________________ &nbsp; {{ now()->format('d-M-Y') }}</div>
        </div>
        <div class="blk">
            <div class="lbl">Employer Seal</div>
            <div style="height:36px"></div>
        </div>
    </div>

    <div class="footer">
        <div>ESIC Monthly Contribution Return (Upload Format) — System generated by Hreasy by WebSenor on {{ now()->format('d-M-Y H:i') }}</div>
        <div>Page 1 of 1</div>
    </div>
</div>

</body>
</html>
