<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Muster Roll — {{ $group->salary_group_name ?? 'Group' }} — {{ $monthLabel }}</title>
    <style>
        @page { size: A4 landscape; margin: 6mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 8px; background: #f1f5f9; color: #000; }
        .actions { max-width: 1700px; margin: 0 auto 8px; display: flex; gap: 8px; justify-content: flex-end }
        .actions button { padding: 6px 14px; background: #DC2626; color: #fff; border: none; border-radius: 3px; cursor: pointer; font-size: 13px }
        .actions button.secondary { background: #64748B }
        .sheet { background: #fff; max-width: 1700px; margin: 0 auto; padding: 6mm; border: 1px solid #ccc; }
        .header { position: relative; margin-bottom: 8px; }
        .company { font-size: 20px; font-weight: bold; }
        .group { font-size: 14px; margin-top: 3px; }
        .meta { font-size: 12px; margin-top: 4px; }
        .meta span { margin-right: 18px; }
        .right-title { position: absolute; top: 0; right: 0; text-align: right; font-size: 15px; font-weight: bold; }
        table.mr { width: 100%; border-collapse: collapse; font-size: 9px; table-layout: fixed; }
        table.mr th, table.mr td { border: 1px solid #000; padding: 2px 2px; text-align: center; vertical-align: middle; }
        table.mr thead th { background: #f3f4f6; font-weight: bold; line-height: 1.1; }
        table.mr td.l { text-align: left; }
        th.day, td.day { width: 16px; }
        th.sun, td.sun { background: #FEE2E2; }
        td.code-P  { background: #D1FAE5; }
        td.code-A  { background: #FEE2E2; color: #991B1B; font-weight: bold; }
        td.code-WO { background: #E2E8F0; color: #475569; }
        td.code-PH { background: #F3E8FF; color: #6B21A8; }
        td.code-HD { background: #FFEDD5; }
        td.code-OD { background: #DBEAFE; }
        td.code-CL, td.code-SL, td.code-PL, td.code-L { background: #FEF3C7; }
        .name { width: 130px; }
        .sumcol { width: 26px; }
        tr.totals-row td { background: #FEF3C7; font-weight: bold; }
        .legend { font-size: 9px; margin-top: 8px; }
        .legend span { margin-right: 12px; white-space: nowrap; }
        .footer { display: flex; justify-content: space-between; margin-top: 18px; font-size: 10px; padding: 0 4px; }
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
        <div class="right-title">Attendance Muster Roll : {{ $monthLabel }}</div>
        <div class="group">{{ $group->salary_group_name ?? '' }}</div>
        <div class="meta">
            <span>Category : <strong>ALL</strong></span>
            <span>Department : <strong>ALL</strong></span>
            <span>Work Place : <strong>ALL</strong></span>
            <span>Grade : <strong>ALL</strong></span>
            <span>Employee Type : <strong>ALL</strong></span>
        </div>
    </div>

    <table class="mr">
        <thead>
            <tr>
                <th rowspan="2" style="width:22px">Sr.<br>No.</th>
                <th rowspan="2" style="width:42px">Emp ID</th>
                <th rowspan="2" class="name">Employee Name<br>/ Designation</th>
                <th colspan="{{ $daysInMonth }}">Days</th>
                <th rowspan="2" class="sumcol">Pre&shy;sents</th>
                <th rowspan="2" class="sumcol">Week Off</th>
                <th rowspan="2" class="sumcol">Paid Hol.</th>
                <th rowspan="2" class="sumcol">Lea&shy;ves</th>
                <th rowspan="2" class="sumcol">Abs.+ESI</th>
                <th rowspan="2" class="sumcol">Pay&shy;ble Days</th>
            </tr>
            <tr>
                @foreach($dayLabels as $d => $info)
                    <th class="day {{ $info['is_sun'] ? 'sun' : '' }}">{{ $d }}<br><span style="font-weight:normal">{{ $info['short'][0] }}</span></th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['sr'] }}</td>
                    <td>{{ $r['emp_id'] }}</td>
                    <td class="l">{{ $r['name'] }}@if($r['designation'])<br><span style="font-size:8px;color:#555">{{ $r['designation'] }}</span>@endif</td>
                    @foreach($dayLabels as $d => $info)
                        @php $code = $r['days'][$d] ?? ''; @endphp
                        <td class="day {{ $code ? 'code-'.$code : ($info['is_sun'] ? 'sun' : '') }}">{{ $code }}</td>
                    @endforeach
                    <td>{{ rtrim(rtrim(number_format($r['present'],2,'.',''),'0'),'.') }}</td>
                    <td>{{ rtrim(rtrim(number_format($r['weekoff'],2,'.',''),'0'),'.') }}</td>
                    <td>{{ rtrim(rtrim(number_format($r['ph'],2,'.',''),'0'),'.') }}</td>
                    <td>{{ rtrim(rtrim(number_format($r['leaves'],2,'.',''),'0'),'.') }}</td>
                    <td>{{ rtrim(rtrim(number_format($r['absent'],2,'.',''),'0'),'.') }}</td>
                    <td>{{ rtrim(rtrim(number_format($r['payable'],2,'.',''),'0'),'.') }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $daysInMonth + 9 }}" style="padding:18px">No employees / attendance found for this period.</td></tr>
            @endforelse
        </tbody>
        @if(count($rows))
        <tfoot>
            <tr class="totals-row">
                <td colspan="3" style="text-align:right">TOTAL</td>
                <td colspan="{{ $daysInMonth }}"></td>
                <td>{{ rtrim(rtrim(number_format($totals['present'],2,'.',''),'0'),'.') }}</td>
                <td>{{ rtrim(rtrim(number_format($totals['weekoff'],2,'.',''),'0'),'.') }}</td>
                <td>{{ rtrim(rtrim(number_format($totals['ph'],2,'.',''),'0'),'.') }}</td>
                <td>{{ rtrim(rtrim(number_format($totals['leaves'],2,'.',''),'0'),'.') }}</td>
                <td>{{ rtrim(rtrim(number_format($totals['absent'],2,'.',''),'0'),'.') }}</td>
                <td>{{ rtrim(rtrim(number_format($totals['payable'],2,'.',''),'0'),'.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="legend">
        <strong>Codes:</strong>
        <span>P = Present</span>
        <span>A = Absent</span>
        <span>WO = Weekly Off</span>
        <span>PH = Paid Holiday</span>
        <span>HD = Half Day</span>
        <span>OD = On Duty</span>
        <span>CL/SL/PL = Leave</span>
        <span style="margin-left:18px">Payble Days = Days in month − Absent</span>
    </div>

    <div class="footer">
        <div>Prepared By: ____________________</div>
        <div>Authorized Signatory: ____________________</div>
    </div>
</div>

<script>
    window.addEventListener('load', () => setTimeout(() => window.print(), 500));
</script>
</body>
</html>
