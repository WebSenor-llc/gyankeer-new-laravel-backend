<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TDS Statement — {{ $monthName }} {{ $year }} — {{ $company->company_name ?? 'Company' }}</title>
    @include('statutory._pdf_styles')
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
                <h1>Income Tax — TDS on Salary (Form 24Q Annexure-II Style)</h1>
                <h2>Monthly Statement of Tax Deducted at Source under Section 192 of the Income Tax Act, 1961</h2>
                <div style="font-size:10.5px;margin-top:2px">As per Sec. 192 read with Rule 21A &amp; Rule 31A — to be aggregated quarterly into Form 24Q</div>
            </div>
            <div class="form-no">
                Form&nbsp;24Q&nbsp;Annx-II<br><small>(System-generated)</small>
            </div>
        </div>
        <hr>
        <div class="meta">
            <div>
                <div><strong>Deductor (Employer):</strong> {{ $company->company_name ?? '—' }}</div>
                <div><strong>TAN:</strong> {{ $company->tan ?? '—' }}</div>
                <div><strong>PAN of Deductor:</strong> {{ $company->pan ?? '—' }}</div>
                <div><strong>Address:</strong>
                    {{ trim(($company->registered_address_line1 ?? '') . ', ' . ($company->registered_address_line2 ?? ''), ', ') ?: '—' }}
                </div>
            </div>
            <div>
                <div><strong>Wage Month:</strong> {{ $monthName }} {{ $year }}</div>
                <div><strong>Quarter:</strong> Q{{ ceil((((int)$month - 3 + 12) % 12 + 1) / 3) }} (FY {{ $month >= 4 ? $year . '-' . substr($year+1, -2) : ($year-1) . '-' . substr($year, -2) }})</div>
                <div><strong>Salary Group:</strong> {{ $group->salary_group_name ?? 'All Groups' }}</div>
                <div><strong>Generated On:</strong> {{ now()->format('d-M-Y H:i') }}</div>
            </div>
        </div>
    </div>

    <table class="gov-table">
        <thead>
            <tr>
                <th style="width:30px">Sr.</th>
                <th style="width:60px">Emp ID</th>
                <th style="width:200px">Employee Name (Deductee)</th>
                <th style="width:100px">PAN</th>
                <th style="width:60px">Regime</th>
                <th style="width:100px">Annual Gross<br>(₹)</th>
                <th style="width:100px">Estimated<br>Annual Tax (₹)</th>
                <th style="width:100px">TDS Deducted<br>This Month (₹)</th>
                <th style="width:80px">Section</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $r)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td class="c">{{ $r->emp_id }}</td>
                    <td class="l">{{ $r->employee_name ?? '—' }}</td>
                    <td class="c">{{ $r->pan ?: '—' }}</td>
                    <td class="c">{{ $r->regime ?? 'New' }}</td>
                    <td class="r">{{ number_format($r->annual_gross ?? 0, 0) }}</td>
                    <td class="r">{{ number_format($r->total_annual_tax ?? 0, 0) }}</td>
                    <td class="r">{{ number_format($r->monthly_tds_for_period ?? 0, 0) }}</td>
                    <td class="c">192</td>
                </tr>
            @empty
                <tr><td colspan="9" class="c" style="padding:24px">No TDS records for this period. Click "Generate TDS" first.</td></tr>
            @endforelse

            @if($rows->count())
                <tr class="totals-row">
                    <td class="label" colspan="5">TOTAL</td>
                    <td class="r">{{ number_format($totals['gross'], 0) }}</td>
                    <td class="r">{{ number_format($totals['tax'], 0) }}</td>
                    <td class="r">₹ {{ number_format($totals['monthly'], 0) }}</td>
                    <td class="c">—</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="summary-box" style="grid-template-columns: repeat(4, 1fr)">
        <div class="cell"><span class="lbl">Total Deductees</span><span class="val">{{ $rows->count() }}</span></div>
        <div class="cell"><span class="lbl">Annual Gross (₹)</span><span class="val">{{ number_format($totals['gross'], 0) }}</span></div>
        <div class="cell"><span class="lbl">Annual Tax Liability (₹)</span><span class="val">{{ number_format($totals['tax'], 0) }}</span></div>
        <div class="cell total"><span class="lbl">Monthly TDS Payable</span><span class="val">₹ {{ number_format($totals['monthly'], 0) }}</span></div>
    </div>

    <div class="declaration">
        <b>Declaration:</b> I/We certify that tax has been deducted at source from the salaries paid/credited to the above
        employees during the month under Section 192 of the Income Tax Act, 1961. The amount of TDS will be deposited to the
        credit of the Central Government on or before the 7th of the following month and quarterly Form 24Q will be filed
        within the prescribed timelines (Rule 31A).
    </div>

    <div class="sigs">
        <div class="blk">
            <div class="lbl">Signature of Person Responsible for Deduction</div>
            <div>{{ $company->authorized_signatory_name ?? '________________________' }}</div>
            <div style="font-size:10px; color:#555">{{ $company->authorized_signatory_designation ?? '' }}</div>
        </div>
        <div class="blk">
            <div class="lbl">Place &amp; Date</div>
            <div>______________________ &nbsp; {{ now()->format('d-M-Y') }}</div>
        </div>
        <div class="blk">
            <div class="lbl">Deductor's Seal</div>
            <div style="height:36px"></div>
        </div>
    </div>

    <div class="footer">
        <div>Form 24Q Annexure-II (Monthly Working) — System generated by Hreasy by WebSenor on {{ now()->format('d-M-Y H:i') }}</div>
        <div>Page 1 of 1</div>
    </div>
</div>

</body>
</html>
