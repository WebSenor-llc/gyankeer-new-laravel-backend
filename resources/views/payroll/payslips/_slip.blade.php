@php
    use Carbon\Carbon;
    $monthName = \DateTime::createFromFormat('!m', $payslip->period_month)->format('F');
    $emp = $payslip->emp;
    $totalDays = (int) Carbon::createFromDate($payslip->period_year, $payslip->period_month, 1)->daysInMonth;

    // ------- Pull attendance summary for the slip's period (if any) -------
    $att = \App\Models\AttendanceSummary::where('emp_id', $emp->emp_id)
        ->where('period_year',  $payslip->period_year)
        ->where('period_month', $payslip->period_month)
        ->first();

    $f = fn ($v, $d = 0.0) => (float) ($v ?? $d);
    $att_present = $f(optional($att)->p_count);
    $att_wo      = $f(optional($att)->w_count);
    $att_ph      = $f(optional($att)->ph_count);
    $att_abs     = $f(optional($att)->a_count);
    $att_cl      = $f(optional($att)->cl_count);
    $att_pl      = $f(optional($att)->pl_count);
    $att_sl      = $f(optional($att)->sl_count);
    $att_hd      = $f(optional($att)->hd_count);
    // CO/CR & Other-leave aren't tracked separately yet; placeholder zeros
    $att_co_cr   = 0.0;
    $att_other   = 0.0;

    // Total Days = sum of calendar-day buckets (HD is a full calendar day where
    // the worker was half-present + half-leave, so it counts as 1 day towards
    // the calendar total, not 0.5).
    $att_total   = $att_present + $att_wo + $att_ph + $att_abs
                 + $att_cl + $att_pl + $att_sl + $att_hd + $att_co_cr + $att_other;

    // Working Days for the salary slip = full presents + half-credit of HD days
    // (only HD's "present-half" counts as worked time).
    $att_working = $att_present + ($att_hd * 0.5);

    $payableDays = (float) ($payslip->payable_days ?? 0);

    // Leave balances — resolved against the payslip's FY (Apr-Dec → next year,
    // Jan-Mar → same year) so the carry-forward shows correctly after import.
    try {
        $slipFy = $payslip->period_month >= 4 ? $payslip->period_year + 1 : $payslip->period_year;
        $cl_bal = optional(\App\Models\LeaveBalance::where('emp_id', $emp->emp_id)
                    ->where('leave_code', 'CL')->where('fy', $slipFy)->first())->closing_balance ?? null;
        $pl_bal = optional(\App\Models\LeaveBalance::where('emp_id', $emp->emp_id)
                    ->where('leave_code', 'PL')->where('fy', $slipFy)->first())->closing_balance ?? null;
    } catch (\Throwable $e) { $cl_bal = null; $pl_bal = null; }

    // Format helper: 0.00 numeric column on the slip
    $fmt = fn ($v) => number_format((float) ($v ?? 0), 2);
    $fmtInt = fn ($v) => number_format((float) ($v ?? 0), 0);

    // Earning components — Basic+DA combined like the GTPPL slip
    $basic_da = $f($payslip->basic) + $f($payslip->da);
    $uniform  = $f($payslip->uniform_allowance ?? 0);   // optional column
    $hra      = $f($payslip->hra);
    $transport = $f($payslip->conveyance);
    $medical  = $f($payslip->medical);
    $spl_hr   = $f($payslip->spl_allow);

    // Deduction labels (kept aligned to GTPPL slip ordering)
    $d_pf       = $f($payslip->epf_emp);
    $d_vpf      = $f($payslip->vpf ?? 0);
    $d_esi      = $f($payslip->esi_emp);
    $d_advance  = $f($payslip->advance_recovery);
    $d_loan     = $f($payslip->loan_emi);
    $d_tds      = $f($payslip->tds);
    $d_rent     = $f($payslip->rent_deduction ?? 0);
    $d_mobile   = $f($payslip->mobile_deduction ?? 0);
    $d_misc_nps = $f($payslip->post_deduction);   // misc/NPS bucket
    $d_security = $f($payslip->security_deduction ?? 0);
    $d_elec     = $f($payslip->electricity_deduction ?? 0);
    $d_donation = $f($payslip->donation_deduction ?? 0);
    $d_pt       = $f($payslip->pt);
    $d_welfare  = $f($payslip->lwf_emp);
    $d_canteen  = $f($payslip->canteen_deduction ?? 0);
    $d_flat     = $f($payslip->flat_rent ?? 0);
    $d_total    = $f($payslip->total_deductions);

    $bankNameStr = $emp->bank->bank_name ?? '';
    if (!empty($emp->bank_branch)) $bankNameStr = trim($bankNameStr . ' ' . $emp->bank_branch);

    $groupLabel = $emp->salary_group->salary_group_name ?? '';
@endphp

<div class="payslip-card" style="background:#fff;border:3px solid #000;font-family:Arial,sans-serif;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact">

    {{-- ===== Company name banner ===== --}}
    <div style="text-align:center;padding:8px 10px 6px;font-size:18px;font-weight:bold">
        {{ $company->company_name ?? 'Company Name' }}
    </div>

    {{-- ===== Salary Group sub-banner ===== --}}
    <div style="text-align:center;padding:3px 10px 6px;font-size:12px;font-weight:bold;border-bottom:1px solid #000">
        {{ $groupLabel }}
    </div>

    {{-- ===== Top info grid (3 columns) ===== --}}
    <table style="width:100%;border-collapse:collapse;font-size:11px">
        <tr>
            {{-- Column 1 — Employee identity --}}
            <td style="vertical-align:top;border-right:1px solid #000;padding:6px 10px;width:38%">
                <table style="width:100%;font-size:11px">
                    <tr><td style="padding:2px 0;font-weight:bold;width:110px">EMCode</td><td>:</td><td>{{ $emp->emp_id }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">Name</td><td>:</td><td>{{ $emp->full_name }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">Fat./Hus. Name</td><td>:</td><td>{{ $emp->fathers_name ?? '' }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">Designation</td><td>:</td><td>{{ $emp->designation->designation_name ?? '' }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">Basic</td><td>:</td><td>{{ $fmtInt($payslip->basic) }}</td></tr>
                </table>
            </td>

            {{-- Column 2 — Statutory & Bank --}}
            <td style="vertical-align:top;border-right:1px solid #000;padding:6px 10px;width:38%">
                <table style="width:100%;font-size:11px">
                    <tr><td style="padding:2px 0;font-weight:bold;width:90px">Month</td><td>:</td><td>{{ $monthName }}/{{ $payslip->period_year }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">PF A/c No.</td><td>:</td><td style="word-break:break-all">{{ $emp->epf_member_id ?? '' }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">E.S.I. No.</td><td>:</td><td>{{ $emp->esi_ip_no ?? '' }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">Bank A/c No.</td><td>:</td><td>{{ $emp->bank_account_no ?? '' }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">Bank Name</td><td>:</td><td>{{ $bankNameStr }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">UAN No.</td><td>:</td><td>{{ $emp->uan ?? '' }}</td></tr>
                </table>
            </td>

            {{-- Column 3 — Leave Balance --}}
            <td style="vertical-align:top;padding:6px 10px;width:24%">
                <div style="text-align:center;font-weight:bold;font-size:13px;margin-bottom:6px">Leave Balance</div>
                <table style="width:100%;font-size:11px">
                    <tr><td style="padding:2px 0;font-weight:bold;width:35px">CL</td><td>:</td><td style="text-align:right">{{ $cl_bal !== null ? number_format($cl_bal, 1) : '—' }}</td></tr>
                    <tr><td style="padding:2px 0;font-weight:bold">PL</td><td>:</td><td style="text-align:right">{{ $pl_bal !== null ? number_format($pl_bal, 0) : '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== Section header bar ===== --}}
    <table style="width:100%;border-collapse:collapse;font-size:11.5px;border-top:1px solid #000">
        <tr style="background:#f1f5f9;font-weight:bold">
            <td style="padding:5px 10px;border-right:1px solid #000;width:34%">ATTENDANCE DETAILS</td>
            <td style="padding:5px 10px;border-right:1px solid #000;width:33%">EARNING DETAILS</td>
            <td style="padding:5px 10px;width:33%">DEDUCTION DETAILS</td>
        </tr>
    </table>

    {{-- ===== Three-column body ===== --}}
    <table style="width:100%;border-collapse:collapse;font-size:11px;border-top:1px solid #000">
        <tr>
            {{-- ATTENDANCE --}}
            <td style="vertical-align:top;border-right:1px solid #000;padding:8px 10px;width:34%">
                <table style="width:100%;font-size:11px">
                    @php
                        $attRows = [
                            ['Working Days', $att_working],
                            ['Weekly off',   $att_wo],
                            ['Paid Holiday', $att_ph],
                            ['Absent',       $att_abs],
                            ['CL',           $att_cl],
                            ['PL',           $att_pl],
                            ['SL',           $att_sl],
                            ['CO/CR',        $att_co_cr],
                            ['Other Leave',  $att_other],
                            ['Total Days',   $att_total],
                        ];
                    @endphp
                    @foreach($attRows as [$lbl, $val])
                        <tr>
                            <td style="padding:2px 0;font-weight:bold;width:110px">{{ $lbl }}</td>
                            <td style="width:10px">:</td>
                            <td style="text-align:right">{{ number_format((float) $val, 1) }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>

            {{-- EARNINGS --}}
            <td style="vertical-align:top;border-right:1px solid #000;padding:8px 10px;width:33%">
                <table style="width:100%;font-size:11px">
                    @php
                        $earnRows = array_filter([
                            ['Basic + DA',                 $basic_da],
                            ['Uniform All. / Academic All.', $uniform],
                            ['HRA',                        $hra],
                            ['Transport All.',             $transport],
                            ['Medical Reim.',              $medical],
                            ['SP / HR Allow.',             $spl_hr],
                            ['Bonus',                      $f($payslip->bonus)],
                            ['Arrear',                     $f($payslip->arrear)],
                        ], fn($r) => $r[1] > 0);
                    @endphp
                    @foreach($earnRows as [$lbl, $val])
                        <tr>
                            <td style="padding:2px 0;font-weight:bold">{{ $lbl }}</td>
                            <td style="width:10px">:</td>
                            <td style="text-align:right">{{ $fmtInt($val) }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>

            {{-- DEDUCTIONS --}}
            <td style="vertical-align:top;padding:8px 10px;width:33%">
                <table style="width:100%;font-size:11px">
                    @php
                        $dedRows = [
                            ['PF',               $d_pf],
                            ['VPF',              $d_vpf],
                            ['E.S.I.',           $d_esi],
                            ['Advance',          $d_advance],
                            ['Loan Amt.',        $d_loan],
                            ['TDS',              $d_tds],
                            ['Rent Ded.',        $d_rent],
                            ['Mobile Ded.',      $d_mobile],
                            ['Misc. Ded./NPS',   $d_misc_nps],
                            ['Security',         $d_security],
                            ['Elect. Ded.',      $d_elec],
                            ['A.G. Donation',    $d_donation],
                            ['Prof. Tax',        $d_pt],
                            ['Welfare',          $d_welfare],
                            ['Can. Ded.',        $d_canteen],
                            ['Flat Rent',        $d_flat],
                        ];
                    @endphp
                    @foreach($dedRows as [$lbl, $val])
                        <tr>
                            <td style="padding:2px 0;font-weight:bold">{{ $lbl }}</td>
                            <td style="width:10px">:</td>
                            <td style="text-align:right">{{ $fmt($val) }}</td>
                        </tr>
                    @endforeach
                    <tr style="border-top:1px solid #000">
                        <td style="padding:4px 0 2px;font-weight:bold;border-top:1px solid #000">Total Ded.</td>
                        <td style="border-top:1px solid #000">:</td>
                        <td style="text-align:right;font-weight:bold;border-top:1px solid #000">{{ $fmt($d_total) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== Bottom totals row ===== --}}
    <table style="width:100%;border-collapse:collapse;border-top:1px solid #000;font-size:13px">
        <tr style="background:#f1f5f9;font-weight:bold">
            <td style="padding:8px 12px;border-right:1px solid #000;width:34%">
                Payable Days &nbsp;&nbsp; <span style="display:inline-block;float:right;font-weight:bold">{{ number_format($payableDays, 1) }}</span>
            </td>
            <td style="padding:8px 12px;border-right:1px solid #000;width:33%">
                GROSS SALARY &nbsp;&nbsp; <span style="display:inline-block;float:right;font-weight:bold">{{ $fmt($payslip->gross_earnings) }}</span>
            </td>
            <td style="padding:8px 12px;width:33%">
                NET PAY &nbsp;&nbsp; <span style="display:inline-block;float:right;font-weight:bold;color:#000">{{ $fmt($payslip->net_pay) }}</span>
            </td>
        </tr>
    </table>

    {{-- ===== Disclaimer footer ===== --}}
    <div style="padding:8px 12px;font-size:10.5px;font-weight:bold">
        Computer Generated Pay Slip Not Required Any Signature.
    </div>

    @if((float) ($payslip->ot_amount ?? 0) > 0)
        <div style="padding:6px 12px;font-size:10px;background:#FEF3C7;border-top:1px solid #FCD34D;color:#92400E">
            <strong>Note:</strong> Overtime of ₹{{ $fmt($payslip->ot_amount) }} for this period is paid separately via the Incentive Register (not part of the above net pay).
        </div>
    @endif
</div>
