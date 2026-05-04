<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Incentive Register — {{ $group->salary_group_name ?? 'All Groups' }} — {{ \DateTime::createFromFormat('!m', $month)->format('M') . '-' . $year }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 12px; background: #f1f5f9; color: #000; }
        .actions { max-width: 900px; margin: 0 auto 10px; display: flex; gap: 8px; justify-content: flex-end; }
        .actions button { padding: 7px 16px; background: #DC2626; color: #fff; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; }
        .actions button.secondary { background: #64748B; }
        .sheet { background: #fff; max-width: 900px; margin: 0 auto; padding: 10mm; border: 1px solid #ccc; }
        .header { text-align: center; margin-bottom: 12px; }
        .company { font-size: 18px; font-weight: bold; }
        .title   { font-size: 13px; font-weight: bold; margin-top: 4px; }
        .group   { font-size: 11px; margin-top: 2px; }
        table.reg { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.reg th, table.reg td { border: 1px solid #000; padding: 4px 6px; text-align: center; vertical-align: middle; }
        table.reg thead th { background: #f3f4f6; font-weight: bold; }
        table.reg td.l { text-align: left; }
        table.reg td.r { text-align: right; }
        table.reg tr.totals td { background: #FEF3C7; font-weight: bold; }
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
        <div class="company">{{ $company->company_name ?? 'Gyankeer Tobacco Products Private Limited' }}</div>
        <div class="title">Incentive for the Month of : {{ \DateTime::createFromFormat('!m', $month)->format('M') . '-' . $year }}</div>
        @if($group)
            <div class="group">Salary Group : {{ $group->salary_group_name }}</div>
        @endif
    </div>

    <table class="reg">
        <thead>
            <tr>
                <th style="width:30px">Sr. No.</th>
                <th style="width:50px">E.Code</th>
                <th style="width:170px">Employee Name</th>
                <th style="width:120px">Designation</th>
                <th style="width:100px">ESI No</th>
                <th style="width:60px">Rate</th>
                <th style="width:55px">Inc. Hours</th>
                <th style="width:70px">Amount</th>
                <th style="width:55px">ESI Ded</th>
                <th style="width:75px">Net Payble</th>
                <th style="width:90px">Signature</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->emp_id }}</td>
                    <td class="l">{{ $r->emp->full_name ?? '—' }}</td>
                    <td class="l">{{ $r->emp->designation->designation_name ?? '—' }}</td>
                    <td>{{ $r->emp->esi_ip_no ?? '—' }}</td>
                    <td class="r">{{ number_format((float)($r->hourly_rate ?? ($r->ot_amount && $r->ot_hours ? $r->ot_amount / $r->ot_hours : 0)), 2) }}</td>
                    <td class="r">{{ rtrim(rtrim(number_format((float)$r->ot_hours, 2),'0'),'.') }}</td>
                    <td class="r">{{ number_format((float)$r->ot_amount, 2) }}</td>
                    <td class="r">{{ number_format((float)($r->ot_esi ?? 0), 2) }}</td>
                    <td class="r">{{ number_format((float)($r->ot_payable ?? $r->ot_amount), 2) }}</td>
                    <td></td>
                </tr>
            @empty
                <tr><td colspan="11" style="padding:30px;color:#666">No incentive records for this period.</td></tr>
            @endforelse

            @if(count($records) > 0)
                <tr class="totals">
                    <td colspan="6" class="r" style="background:#FCD34D">Total ::</td>
                    <td class="r">{{ number_format($totals['hours'], 2) }}</td>
                    <td class="r">{{ number_format($totals['amount'], 2) }}</td>
                    <td class="r">{{ number_format($totals['esi'], 2) }}</td>
                    <td class="r">{{ number_format($totals['payable'], 2) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<script>
    window.addEventListener('load', () => setTimeout(() => window.print(), 500));
</script>
</body>
</html>
