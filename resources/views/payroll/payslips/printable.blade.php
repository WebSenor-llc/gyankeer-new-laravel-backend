<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip — {{ $payslip->emp->full_name }} — {{ \DateTime::createFromFormat('!m', $payslip->period_month)->format('F') }} {{ $payslip->period_year }}</title>
    <style>
        * { box-sizing: border-box }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f1f5f9; }
        .payslip-card { background:#fff; max-width:800px; margin:0 auto; border:1px solid #cbd5e1; }
        .actions { max-width:800px; margin:0 auto 12px; display:flex; gap:8px; justify-content:flex-end }
        .actions button { padding:8px 16px; background:#0EA5E9; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px }
        .actions button.secondary { background:#64748B }
        @media print {
            body { background:#fff; padding:0 }
            .actions { display:none }
            .payslip-card { border:none; max-width:100% }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">🖨 Print / Save as PDF</button>
        <button class="secondary" onclick="window.close()">Close</button>
    </div>

    @include('payroll.payslips._slip', ['payslip' => $payslip, 'company' => $company])

    <script>
        // Auto-open print dialog after a brief render delay
        // window.addEventListener('load', () => setTimeout(() => window.print(), 400));
    </script>
</body>
</html>
