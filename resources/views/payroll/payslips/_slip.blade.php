@php
    use Carbon\Carbon;
    $monthName = \DateTime::createFromFormat('!m', $payslip->period_month)->format('F');
    $emp = $payslip->emp;
    $totalDays = (int) Carbon::createFromDate($payslip->period_year, $payslip->period_month, 1)->daysInMonth;

    // Earnings & deductions tables
    $earnings = [
        ['Basic',          (float) $payslip->basic],
        ['House Rent Allowance (HRA)', (float) $payslip->hra],
        ['Dearness Allowance (DA)',    (float) $payslip->da],
        ['Conveyance',     (float) $payslip->conveyance],
        ['Medical',        (float) $payslip->medical],
        ['Special Allowance', (float) $payslip->spl_allow],
        ['Bonus',          (float) $payslip->bonus],
        ['Arrear',         (float) $payslip->arrear],
        // Overtime is intentionally NOT shown here — it's paid SEPARATELY via
        // the Overtime Sheet/Incentive Register, not through the salary slip.
    ];
    $earnings = array_filter($earnings, fn($r) => $r[1] > 0);

    $deductions = [
        ['EPF (Employee)',  (float) $payslip->epf_emp],
        ['VPF (Voluntary)', (float) ($payslip->vpf ?? 0)],
        ['ESI (Employee)',  (float) $payslip->esi_emp],
        ['Professional Tax', (float) $payslip->pt],
        ['LWF',             (float) $payslip->lwf_emp],
        ['Income Tax (TDS)', (float) $payslip->tds],
        ['Loan EMI',        (float) $payslip->loan_emi],
        ['Advance Recovery', (float) $payslip->advance_recovery],
        ['Fine Recovery',    (float) $payslip->fine_recovery],
        ['Other Deductions', (float) $payslip->post_deduction],
    ];
    $deductions = array_filter($deductions, fn($r) => $r[1] > 0);

    // Convert net pay to words (simple Indian numbering)
    function rupeesInWords(float $amt): string {
        if ($amt < 0.005) return 'Zero';
        $whole = (int) floor($amt);
        $paise = (int) round(($amt - $whole) * 100);
        $words = numToIndianWords($whole) . ' Rupees';
        if ($paise > 0) $words .= ' and ' . numToIndianWords($paise) . ' Paise';
        return $words . ' Only';
    }
    function numToIndianWords(int $n): string {
        if ($n == 0) return 'Zero';
        $a = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        $b = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        $word = function ($num) use (&$word, $a, $b) {
            if ($num < 20) return $a[$num];
            if ($num < 100) return trim($b[(int)($num/10)] . ' ' . $a[$num%10]);
            return trim($a[(int)($num/100)] . ' Hundred ' . ($num%100 ? $word($num%100) : ''));
        };
        $crore = (int) floor($n / 10000000);  $n %= 10000000;
        $lakh  = (int) floor($n / 100000);    $n %= 100000;
        $thou  = (int) floor($n / 1000);      $n %= 1000;
        $rest  = $n;
        $parts = [];
        if ($crore) $parts[] = $word($crore) . ' Crore';
        if ($lakh)  $parts[] = $word($lakh)  . ' Lakh';
        if ($thou)  $parts[] = $word($thou)  . ' Thousand';
        if ($rest)  $parts[] = $word($rest);
        return trim(implode(' ', $parts));
    }
@endphp

<div class="card payslip-card" style="background:#fff;border:1px solid #cbd5e1;font-family:Arial,sans-serif">

    {{-- Company header --}}
    <div style="text-align:center;padding:16px 20px 10px;border-bottom:2px solid #991B1B">
        <div style="font-size:22px;font-weight:bold;color:#991B1B;letter-spacing:0.5px">{{ $company->company_name ?? 'Company Name' }}</div>
        @if($company)
            <div style="font-size:11px;color:#64748B">
                @if($company->registered_address){{ $company->registered_address }}@endif
                @if($company->city), {{ $company->city }}@endif
                @if($company->state), {{ $company->state }}@endif
                @if($company->pin) — {{ $company->pin }}@endif
            </div>
            <div style="font-size:11px;color:#64748B;margin-top:2px">
                @if($company->pan_no)PAN: {{ $company->pan_no }}@endif
                @if($company->gstin) · GSTIN: {{ $company->gstin }}@endif
            </div>
        @endif
        <div style="font-size:14px;font-weight:bold;color:#1E293B;margin-top:8px;background:#FEE2E2;display:inline-block;padding:3px 14px;border-radius:3px">
            Salary Slip for {{ $monthName }} {{ $payslip->period_year }}
        </div>
    </div>

    {{-- Employee details --}}
    <table style="width:100%;font-size:12px;border-collapse:collapse">
        <tr>
            <td style="padding:6px 12px;border-right:1px solid #e2e8f0;width:50%;vertical-align:top">
                <table style="font-size:12px;width:100%">
                    <tr><td style="color:#64748B;padding:2px 0;width:120px">Employee ID</td><td><strong>{{ $emp->emp_id }}</strong></td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">Name</td><td><strong>{{ $emp->full_name }}</strong></td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">Father / Husband</td><td>{{ $emp->fathers_name ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">Department</td><td>{{ $emp->department->dept_name ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">Designation</td><td>{{ $emp->designation->designation_name ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">Salary Group</td><td>{{ $emp->salary_group->salary_group_name ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">Date of Joining</td><td>{{ $emp->date_of_joining ?? '—' }}</td></tr>
                </table>
            </td>
            <td style="padding:6px 12px;width:50%;vertical-align:top">
                <table style="font-size:12px;width:100%">
                    <tr><td style="color:#64748B;padding:2px 0;width:120px">PAN</td><td>{{ $emp->pan_no ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">UAN</td><td>{{ $emp->uan ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">PF Number</td><td>{{ $emp->epf_member_id ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">ESI Number</td><td>{{ $emp->esi_ip_no ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">Bank</td><td>{{ $emp->bank->bank_name ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">A/C No</td><td>{{ $emp->bank_account_no ?? '—' }}</td></tr>
                    <tr><td style="color:#64748B;padding:2px 0">IFSC</td><td>{{ $emp->bank_ifsc ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Attendance --}}
    <table style="width:100%;font-size:12px;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;background:#F8FAFC">
        <tr>
            <td style="padding:6px 12px;width:25%"><span style="color:#64748B">Total Days</span><br><strong>{{ $totalDays }}</strong></td>
            <td style="padding:6px 12px;width:25%"><span style="color:#64748B">Payable Days</span><br><strong>{{ $payslip->payable_days ?? '—' }}</strong></td>
            <td style="padding:6px 12px;width:25%"><span style="color:#64748B">LOP Days</span><br><strong>{{ $payslip->lop_days ?? 0 }}</strong></td>
            <td style="padding:6px 12px;width:25%"><span style="color:#64748B">Present Days</span><br><strong>{{ $payslip->present_days ?? '—' }}</strong></td>
        </tr>
    </table>

    {{-- Earnings + Deductions side-by-side --}}
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <tr style="background:#F1F5F9">
            <th colspan="2" style="text-align:left;padding:8px 12px;border-right:1px solid #cbd5e1">Earnings</th>
            <th colspan="2" style="text-align:left;padding:8px 12px">Deductions</th>
        </tr>
        @php
            $maxRows = max(count($earnings), count($deductions));
            $earnings = array_values($earnings);
            $deductions = array_values($deductions);
        @endphp
        @for($i = 0; $i < $maxRows; $i++)
            <tr>
                <td style="padding:5px 12px;border-bottom:1px solid #e2e8f0;border-right:1px solid #cbd5e1;width:30%">
                    {{ $earnings[$i][0] ?? '' }}
                </td>
                <td style="padding:5px 12px;border-bottom:1px solid #e2e8f0;border-right:1px solid #cbd5e1;text-align:right;width:20%">
                    @if(isset($earnings[$i]))&#8377;{{ number_format($earnings[$i][1], 2) }}@endif
                </td>
                <td style="padding:5px 12px;border-bottom:1px solid #e2e8f0;width:30%">
                    {{ $deductions[$i][0] ?? '' }}
                </td>
                <td style="padding:5px 12px;border-bottom:1px solid #e2e8f0;text-align:right;width:20%">
                    @if(isset($deductions[$i]))&#8377;{{ number_format($deductions[$i][1], 2) }}@endif
                </td>
            </tr>
        @endfor
        <tr style="background:#FEF2F2;font-weight:bold">
            <td style="padding:8px 12px;border-right:1px solid #cbd5e1">Gross Earnings</td>
            <td style="padding:8px 12px;border-right:1px solid #cbd5e1;text-align:right">&#8377;{{ number_format((float)$payslip->gross_earnings, 2) }}</td>
            <td style="padding:8px 12px">Total Deductions</td>
            <td style="padding:8px 12px;text-align:right">&#8377;{{ number_format((float)$payslip->total_deductions, 2) }}</td>
        </tr>
    </table>

    {{-- Net Pay --}}
    <div style="background:#16A34A;color:#fff;padding:14px 20px;text-align:center">
        <div style="font-size:13px;opacity:0.9">NET PAY</div>
        <div style="font-size:24px;font-weight:bold;letter-spacing:1px">&#8377; {{ number_format((float)$payslip->net_pay, 2) }}</div>
        <div style="font-size:11px;opacity:0.95;margin-top:3px;font-style:italic">{{ rupeesInWords((float)$payslip->net_pay) }}</div>
    </div>

    @if((float) ($payslip->ot_amount ?? 0) > 0)
        <div style="padding:8px 20px;font-size:11px;background:#FEF3C7;border-top:1px solid #FCD34D;color:#92400E;text-align:center">
            <strong>Note:</strong> Overtime of ₹{{ number_format((float)$payslip->ot_amount, 2) }} for this period is paid separately via the Incentive Register (not part of the above net pay).
        </div>
    @endif

    {{-- Footer --}}
    <div style="padding:10px 20px;font-size:10px;color:#64748B;text-align:center;border-top:1px solid #e2e8f0">
        This is a computer-generated payslip. No signature required.
        Generated on {{ optional($payslip->generated_at)->format('d M Y') ?? now()->format('d M Y') }} ·
        Run: {{ $payslip->run->run_code ?? '—' }}
    </div>
</div>
