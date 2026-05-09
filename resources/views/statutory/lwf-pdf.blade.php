<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LWF Half-Yearly Return — {{ $halfLabel }} {{ $year }} — {{ $company->company_name ?? 'Company' }}</title>
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
                <h1>Labour Welfare Fund (LWF) — Half-Yearly Contribution Return</h1>
                <h2>Statement of Employee &amp; Employer LWF Contributions</h2>
                <div style="font-size:10.5px;margin-top:2px">Filed under State Labour Welfare Fund Acts (e.g. MH LWF Act 1953 / KA LWF Act 1965 / TN LWF Act 1972)</div>
            </div>
            <div class="form-no">
                Form&nbsp;A-1<br><small>(System-generated)</small>
            </div>
        </div>
        <hr>
        <div class="meta">
            <div>
                <div><strong>Employer Name:</strong> {{ $company->company_name ?? '—' }}</div>
                <div><strong>LWF State:</strong> {{ $company->lwf_state ?? '—' }}</div>
                <div><strong>Address:</strong>
                    {{ trim(($company->registered_address_line1 ?? '') . ', ' . ($company->registered_address_line2 ?? ''), ', ') ?: '—' }}
                </div>
                <div><strong>PAN:</strong> {{ $company->pan ?? '—' }}</div>
            </div>
            <div>
                <div><strong>Period:</strong> {{ $halfLabel }} {{ $year }}</div>
                <div><strong>Salary Group:</strong> {{ $group->salary_group_name ?? 'All Groups' }}</div>
                <div><strong>Total Employees:</strong> {{ $rows->count() }}</div>
                <div><strong>Generated On:</strong> {{ now()->format('d-M-Y H:i') }}</div>
            </div>
        </div>
    </div>

    <table class="gov-table">
        <thead>
            <tr>
                <th style="width:30px">Sr.</th>
                <th style="width:60px">Emp ID</th>
                <th style="width:240px">Employee Name</th>
                <th style="width:80px">State</th>
                <th style="width:90px">EE Contribution<br>(₹)</th>
                <th style="width:90px">ER Contribution<br>(₹)</th>
                <th style="width:100px">Total<br>(₹)</th>
                <th style="width:80px">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $r)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td class="c">{{ $r->emp_id }}</td>
                    <td class="l">{{ $r->employee_name ?? '—' }}</td>
                    <td class="c">{{ $r->state ?? '—' }}</td>
                    <td class="r">{{ number_format($r->employee_contribution ?? 0, 0) }}</td>
                    <td class="r">{{ number_format($r->employer_contribution ?? 0, 0) }}</td>
                    <td class="r">{{ number_format($r->total_contribution ?? 0, 0) }}</td>
                    <td class="c">{{ $r->status ?? 'Generated' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="c" style="padding:24px">No LWF records for this period. Click "Generate LWF" first.</td></tr>
            @endforelse

            @if($rows->count())
                <tr class="totals-row">
                    <td class="label" colspan="4">TOTAL LWF REMITTANCE</td>
                    <td class="r">{{ number_format($totals['ee'], 0) }}</td>
                    <td class="r">{{ number_format($totals['er'], 0) }}</td>
                    <td class="r">₹ {{ number_format($totals['total'], 0) }}</td>
                    <td class="c">—</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="declaration">
        <b>Declaration:</b> I/We hereby certify that the above contributions have been deducted from the wages of the employees
        named herein in accordance with the applicable State Labour Welfare Fund Act, and that the corresponding employer
        contribution will be deposited to the State LWF Board on or before the prescribed half-yearly due date.
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
        <div>LWF Half-Yearly Return — System generated by Hreasy by WebSenor on {{ now()->format('d-M-Y H:i') }}</div>
        <div>Page 1 of 1</div>
    </div>
</div>

</body>
</html>
