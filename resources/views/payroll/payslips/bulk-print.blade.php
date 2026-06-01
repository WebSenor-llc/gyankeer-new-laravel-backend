<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslips — {{ $group->salary_group_name ?? 'All Groups' }} — {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</title>
    <style>
        * { box-sizing: border-box }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f1f5f9; }
        .actions { max-width:800px; margin:0 auto 12px; display:flex; gap:8px; justify-content:flex-end; align-items:center }
        .actions .meta { margin-right:auto; font-size:13px; color:#334155 }
        .actions button { padding:8px 16px; background:#0EA5E9; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px }
        .actions button.secondary { background:#64748B }
        .slip-page { page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; }
        .slip-page:last-child { page-break-after: auto; break-after: auto; }
        @media print {
            body { background:#fff; padding:0 }
            .actions { display:none }
            .payslip-card { border:none; max-width:100% }
        }
    </style>
</head>
<body>
    <div class="actions">
        <div class="meta">
            {{ $group->salary_group_name ?? 'All Groups' }} —
            {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
            ({{ $payslips->count() }} payslips)
        </div>
        <button onclick="window.print()">🖨 Print / Save as PDF (All)</button>
        <button class="secondary" onclick="window.close()">Close</button>
    </div>

    @foreach($payslips as $payslip)
        <div class="slip-page">
            @include('payroll.payslips._slip', ['payslip' => $payslip, 'company' => $company])
        </div>
    @endforeach
</body>
</html>
