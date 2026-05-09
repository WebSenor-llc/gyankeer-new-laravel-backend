<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Sheet — {{ $group->salary_group_name ?? 'Group' }} — {{ $monthLabel }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 8px; background: #f1f5f9; color: #000; }
        .actions { max-width: 1700px; margin: 0 auto 8px; display: flex; gap: 8px; justify-content: flex-end }
        .actions button { padding: 6px 14px; background: #DC2626; color: #fff; border: none; border-radius: 3px; cursor: pointer; font-size: 13px }
        .actions button.secondary { background: #64748B }
        .sheet { background: #fff; max-width: 1700px; margin: 0 auto; padding: 8mm; border: 1px solid #ccc; }
        .header { position: relative; margin-bottom: 8px; }
        .company { font-size: 22px; font-weight: bold; }
        .group { font-size: 15px; margin-top: 3px; }
        .month { font-size: 14px; margin-top: 2px; }
        .right-title { position: absolute; top: 0; right: 0; text-align: right; font-size: 14px; font-weight: bold; }
        .right-title small { font-weight: normal; font-size: 12px; }
        table.sheet-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        table.sheet-table th, table.sheet-table td { border: 1px solid #000; padding: 4px 5px; text-align: center; vertical-align: top; }
        table.sheet-table thead th { background: #f3f4f6; font-weight: bold; font-size: 11px; line-height: 1.2; padding: 5px 4px; }
        table.sheet-table td.l { text-align: left; }
        table.sheet-table td.r { text-align: right; }
        table.sheet-table td.stack { text-align: left; line-height: 1.25; padding: 2px 4px; }
        table.sheet-table td.stack span { display: block; }
        table.sheet-table td.num-stack { text-align: right; line-height: 1.25; padding: 2px 4px; }
        table.sheet-table td.num-stack span { display: block; }
        tr.totals-row td { background: #FEF3C7; font-weight: bold; }
        tr.totals-row td.label { background: #FCD34D; text-align: center; font-size: 11px; }
        .footer { display: flex; justify-content: space-between; margin-top: 14px; font-size: 9px; padding: 0 4px; }
        .page-num { text-align: right; font-size: 8px; margin-top: 4px; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .sheet { border: none; max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">🖨 Save as PDF</button>
        <button class="secondary" onclick="window.close()">Close</button>
    </div>

<div class="sheet">
    <div class="header">
        <div class="company">{{ $company->company_name ?? 'Company' }}</div>
        <div class="group">{{ $group->salary_group_name ?? '' }}</div>
        <div class="month">Salary Sheet For the Month of : <strong>{{ $monthLabel }}</strong></div>
        <div class="right-title">
            Payment of Wages Register<br>
            <small>(Applicable to all Labour Acts)</small>
        </div>
    </div>

    <table class="sheet-table">
        <thead>
            <tr>
                <th rowspan="2" style="width:24px">Sr.<br>No.</th>
                <th rowspan="2" style="width:42px">EMCode</th>
                <th rowspan="2" style="width:120px">Name<br>Father/Husband Name<br>Designation</th>
                <th rowspan="2" style="width:90px">P.F. No.<br>UAN No.<br>E.S.I. No.<br>PAN No.</th>
                <th style="width:36px">W. Days<br>Leaves<br>P.H.</th>
                <th style="width:36px">W. O.<br>Abs.<br>P. Days</th>
                <th style="width:60px">Basic+DA<br>HRA<br>Transport All.<br>Basic Arr.</th>
                <th style="width:60px">Uniform All. /<br>Academic All.<br>Med. Reimb.<br>SP/HR All.<br>Oth. Arr.</th>
                <th style="width:50px">Other Fixed<br>All.</th>
                <th rowspan="2" style="width:54px">Total</th>
                <th style="width:46px">ESI<br>TDS<br>P.F.<br>P.F.Arr</th>
                <th style="width:46px">Loan<br>Adv.<br>Maint.<br>Flat</th>
                <th style="width:46px">Cant.D.<br>Mobile<br>Rent D.<br>W.F.</th>
                <th style="width:46px">P.T.<br>A.G. Ded.<br>Misc./NPS<br>LWF</th>
                <th rowspan="2" style="width:54px">Total<br>Ded.</th>
                <th rowspan="2" style="width:54px">Net Payble</th>
                <th rowspan="2" style="width:90px">Signature/ Remitted<br>in Bank A/c No.</th>
            </tr>
            <tr>
                {{-- Spacer row to lock the rowspan-2 cells (none of these will render content) --}}
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['sr'] }}</td>
                    <td>{{ $r['emp_id'] }}</td>
                    <td class="stack">
                        <span>{{ $r['name'] }}</span>
                        <span style="color:#374151">{{ $r['father'] }}</span>
                        <span style="color:#6B7280;font-size:10.5px">{{ $r['designation'] }}</span>
                    </td>
                    <td class="stack" style="font-size:10.5px">
                        <span>{{ $r['pf_no'] }}</span>
                        <span>{{ $r['uan'] }}</span>
                        <span>{{ $r['esi_no'] }}</span>
                        <span>{{ $r['pan'] }}</span>
                    </td>
                    <td class="num-stack">
                        <span>{{ number_format($r['wdays'], 2) }}</span>
                        <span>{{ number_format($r['leaves'], 2) }}</span>
                        <span>{{ number_format($r['ph'], 2) }}</span>
                    </td>
                    <td class="num-stack">
                        <span>{{ number_format($r['wo'], 2) }}</span>
                        <span>{{ number_format($r['abs'], 2) }}</span>
                        <span>{{ number_format($r['pdays'], 2) }}</span>
                    </td>
                    <td class="num-stack">
                        <span>{{ number_format($r['basic_da'], 0) }}</span>
                        <span>{{ number_format($r['hra'], 0) }}</span>
                        <span>{{ number_format($r['transport'], 0) }}</span>
                        <span>{{ number_format($r['basic_arr'], 0) }}</span>
                    </td>
                    <td class="num-stack">
                        <span>{{ number_format($r['uniform'], 0) }}</span>
                        <span>{{ number_format($r['med'], 0) }}</span>
                        <span>{{ number_format($r['sphr'], 0) }}</span>
                        <span>{{ number_format($r['oth_arr'], 0) }}</span>
                    </td>
                    <td class="r">{{ number_format($r['other_fixed'], 0) }}</td>
                    <td class="r"><strong>{{ number_format($r['gross'], 0) }}</strong></td>
                    <td class="num-stack">
                        <span>{{ number_format($r['esi'], 0) }}</span>
                        <span>{{ number_format($r['tds'], 0) }}</span>
                        <span>{{ number_format($r['pf'], 0) }}</span>
                        <span>{{ number_format($r['pf_arr'], 0) }}</span>
                    </td>
                    <td class="num-stack">
                        <span>{{ number_format($r['loan'], 0) }}</span>
                        <span>{{ number_format($r['adv'], 0) }}</span>
                        <span>{{ number_format($r['maint'], 0) }}</span>
                        <span>{{ number_format($r['flat'], 0) }}</span>
                    </td>
                    <td class="num-stack">
                        <span>{{ number_format($r['cant'], 0) }}</span>
                        <span>{{ number_format($r['mobile'], 0) }}</span>
                        <span>{{ number_format($r['rent'], 0) }}</span>
                        <span>{{ number_format($r['wf'], 0) }}</span>
                    </td>
                    <td class="num-stack">
                        <span>{{ number_format($r['pt'], 0) }}</span>
                        <span>{{ number_format($r['ag'], 0) }}</span>
                        <span>{{ number_format($r['misc'], 0) }}</span>
                        <span>{{ number_format($r['lwf'], 0) }}</span>
                    </td>
                    <td class="r"><strong>{{ number_format($r['total_ded'], 0) }}</strong></td>
                    <td class="r"><strong>{{ number_format($r['net'], 0) }}</strong></td>
                    <td class="stack" style="font-size:10.5px">
                        <span>{{ $r['bank_name'] }}</span>
                        <span>{{ $r['bank_acno'] }}</span>
                    </td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td class="label" colspan="4">TOTAL</td>
                <td class="num-stack">
                    <span>{{ number_format($totals['wdays'], 2) }}</span>
                    <span>{{ number_format($totals['leaves'], 2) }}</span>
                    <span>{{ number_format($totals['ph'], 2) }}</span>
                </td>
                <td class="num-stack">
                    <span>{{ number_format($totals['wo'], 2) }}</span>
                    <span>{{ number_format($totals['abs'], 2) }}</span>
                    <span>{{ number_format($totals['pdays'], 2) }}</span>
                </td>
                <td class="num-stack">
                    <span>{{ number_format($totals['basic_da'], 0) }}</span>
                    <span>{{ number_format($totals['hra'], 0) }}</span>
                    <span>{{ number_format($totals['transport'], 0) }}</span>
                    <span>{{ number_format($totals['basic_arr'], 0) }}</span>
                </td>
                <td class="num-stack">
                    <span>{{ number_format($totals['uniform'], 0) }}</span>
                    <span>{{ number_format($totals['med'], 0) }}</span>
                    <span>{{ number_format($totals['sphr'], 0) }}</span>
                    <span>{{ number_format($totals['oth_arr'], 0) }}</span>
                </td>
                <td class="r">{{ number_format($totals['other_fixed'], 0) }}</td>
                <td class="r">{{ number_format($totals['gross'], 0) }}</td>
                <td class="num-stack">
                    <span>{{ number_format($totals['esi'], 0) }}</span>
                    <span>{{ number_format($totals['tds'], 0) }}</span>
                    <span>{{ number_format($totals['pf'], 0) }}</span>
                    <span>{{ number_format($totals['pf_arr'], 0) }}</span>
                </td>
                <td class="num-stack">
                    <span>{{ number_format($totals['loan'], 0) }}</span>
                    <span>{{ number_format($totals['adv'], 0) }}</span>
                    <span>{{ number_format($totals['maint'], 0) }}</span>
                    <span>{{ number_format($totals['flat'], 0) }}</span>
                </td>
                <td class="num-stack">
                    <span>{{ number_format($totals['cant'], 0) }}</span>
                    <span>{{ number_format($totals['mobile'], 0) }}</span>
                    <span>{{ number_format($totals['rent'], 0) }}</span>
                    <span>{{ number_format($totals['wf'], 0) }}</span>
                </td>
                <td class="num-stack">
                    <span>{{ number_format($totals['pt'], 0) }}</span>
                    <span>{{ number_format($totals['ag'], 0) }}</span>
                    <span>{{ number_format($totals['misc'], 0) }}</span>
                    <span>{{ number_format($totals['lwf'], 0) }}</span>
                </td>
                <td class="r">{{ number_format($totals['total_ded'], 0) }}</td>
                <td class="r">{{ number_format($totals['net'], 0) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>Rounded of Rupees:</div>
        <div>Prepared By: ____________</div>
        <div>Authorized Signatory: ____________</div>
    </div>
</div>

<script>
    // Auto-open print dialog after layout settles
    window.addEventListener('load', () => setTimeout(() => window.print(), 500));
</script>
</body>
</html>
