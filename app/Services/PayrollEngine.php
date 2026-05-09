<?php

namespace App\Services;

use App\Models\AttendanceDaily;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Models\Payslip;
use App\Models\SalaryRun;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Hreasy by WebSenor — Payroll Engine
 * ====================================
 * Orchestrates monthly salary computation: earnings, statutory deductions,
 * employer contributions, net pay and CTC. Plug-in calculators for each
 * statutory head are injected via constructor.
 */
class PayrollEngine
{
    public function __construct(
        protected PFCalculator       $pf,
        protected ESICalculator      $esi,
        protected PTCalculator       $pt,
        protected LWFCalculator      $lwf,
        protected TaxCalculator      $tax,
        protected GratuityCalculator $gratuity,
        protected BonusCalculator    $bonus,
    ) {}

    /**
     * Run payroll for a company / period and persist Payslip rows.
     *
     * @param  int  $companyId
     * @param  int  $year
     * @param  int  $month
     * @return SalaryRun
     */
    public function run(int $companyId, int $year, int $month): SalaryRun
    {
        return DB::transaction(function () use ($companyId, $year, $month) {

            $run = SalaryRun::create([
                'run_code'     => sprintf('SALRUN-%04d-%02d', $year, $month),
                'period_year'  => $year,
                'period_month' => $month,
                'company_id'   => $companyId,
                'status'       => 'Draft',
                'run_started_at' => now(),
                'created_by'   => auth()->user()?->name ?? 'system',
            ]);

            $employees = Employee::where('company_id', $companyId)
                ->where('active_flag', true)
                ->where('employment_status', 'Active')
                ->cursor();

            $totals = [
                'earnings' => 0, 'deductions' => 0, 'net' => 0, 'ctc' => 0,
                'pf_emp' => 0, 'pf_er' => 0, 'eps' => 0, 'edli' => 0, 'admin' => 0,
                'esi_emp' => 0, 'esi_er' => 0, 'pt' => 0, 'lwf_emp' => 0, 'lwf_er' => 0,
                'tds' => 0, 'bonus' => 0, 'gratuity' => 0,
            ];

            foreach ($employees as $emp) {
                $payslip = $this->computeForEmployee($emp, $run);
                foreach (['earnings','deductions','net','ctc'] as $k) {
                    $totals[$k] += $payslip->{
                        match($k){
                            'earnings'=>'gross_earnings',
                            'deductions'=>'total_deductions',
                            'net'=>'net_pay',
                            'ctc'=>'total_employer_cost',
                        }
                    };
                }
                $totals['pf_emp']  += $payslip->epf_emp;
                $totals['pf_er']   += $payslip->employer_pf;
                $totals['eps']     += $payslip->eps;
                $totals['edli']    += $payslip->edli;
                $totals['admin']   += $payslip->pf_admin;
                $totals['esi_emp'] += $payslip->esi_emp;
                $totals['esi_er']  += $payslip->employer_esi;
                $totals['pt']      += $payslip->pt;
                $totals['lwf_emp'] += $payslip->lwf_emp;
                $totals['lwf_er']  += $payslip->lwf_employer;
                $totals['tds']     += $payslip->tds;
                $totals['bonus']   += $payslip->bonus;
                $totals['gratuity']+= $payslip->gratuity_provision;
            }

            $run->update([
                'eligible_emp_count'         => $employees->count(),
                'total_earnings'             => $totals['earnings'],
                'total_deductions'           => $totals['deductions'],
                'total_net_payout'           => $totals['net'],
                'total_employer_cost'        => $totals['ctc'],
                'total_pf_emp'               => $totals['pf_emp'],
                'total_pf_er'                => $totals['pf_er'],
                'total_eps'                  => $totals['eps'],
                'total_edli'                 => $totals['edli'],
                'total_admin'                => $totals['admin'],
                'total_esi_emp'              => $totals['esi_emp'],
                'total_esi_er'               => $totals['esi_er'],
                'total_pt'                   => $totals['pt'],
                'total_lwf_emp'              => $totals['lwf_emp'],
                'total_lwf_er'               => $totals['lwf_er'],
                'total_tds'                  => $totals['tds'],
                'total_bonus_provision'      => $totals['bonus'],
                'total_gratuity_provision'   => $totals['gratuity'],
                'calc_completed_at'          => now(),
            ]);

            return $run->fresh();
        });
    }

    /**
     * Compute one employee's payslip for a period.
     */
    public function computeForEmployee(Employee $e, SalaryRun $run): Payslip
    {
        // Prefer the explicit salary structure if one exists; otherwise use
        // the values stored on the employee master (populated from the
        // imported Excel) so the computed payslip exactly matches what's
        // already shown on the Salary tab.
        $structure = SalaryStructure::where('emp_id', $e->emp_id)
            ->where('status', 'Active')
            ->latest('effective_from')
            ->first();

        $basic = (float) ($structure?->basic       ?? $e->current_basic ?? 0);
        $hra   = (float) ($structure?->hra         ?? $e->current_hra   ?? 0);
        $da    = (float) ($structure?->da          ?? $e->current_da    ?? 0);
        $conv  = (float) ($structure?->conveyance  ?? (is_numeric($e->current_conv ?? 0) ? $e->current_conv : 0));
        $med   = (float) ($structure?->medical     ?? (is_numeric($e->current_med  ?? 0) ? $e->current_med  : 0));
        $spl   = (float) ($structure?->special_allowance ?? $e->current_spl ?? 0);

        // If gross is set but components don't add up (Excel imports often had
        // House Rent / Education on top), backfill the residual into spl_allow.
        $sum   = $basic + $hra + $da + $conv + $med + $spl;
        $gross = (float) ($e->current_gross ?? $sum);
        if ($gross > 0 && $sum < $gross) {
            $spl += ($gross - $sum);
        } elseif ($gross <= 0) {
            $gross = $sum;
        }

        // === Attendance-driven proration ===
        // Total days in this period (28/29/30/31)
        $totalDays = (int) Carbon::createFromDate($run->period_year, $run->period_month, 1)->daysInMonth;

        // === Final Settlement (FNF) — exited employees ===
        // If date_of_relieving falls inside this month, cap the period to that
        // date. If it's BEFORE this month, the engine query already excluded
        // the employee. If it's AFTER this month, normal payroll runs.
        $isFinalSettlement = false;
        if (\Illuminate\Support\Facades\Schema::hasColumn('employees', 'date_of_relieving') && !empty($e->date_of_relieving)) {
            $relieve = Carbon::parse($e->date_of_relieving);
            $periodStart = Carbon::createFromDate($run->period_year, $run->period_month, 1)->startOfDay();
            $periodEnd   = (clone $periodStart)->endOfMonth();
            if ($relieve->lt($periodStart)) {
                // Already left before this month — should not be on payroll.
                throw new \RuntimeException("Employee {$e->emp_id} relieved on {$relieve->toDateString()}, before period start.");
            }
            if ($relieve->lte($periodEnd)) {
                // Mid-month exit → only count up to relieving day
                $totalDays = (int) $relieve->day;
                $isFinalSettlement = true;
            }
        }

        // Pull attendance for this employee in this period
        $start = Carbon::createFromDate($run->period_year, $run->period_month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth();

        // ── Prefer attendance_summary (raw fractional counts entered via
        //    /attendance/counts). Falls back to attendance_daily for legacy data. ──
        $absentDays  = 0;
        $halfDays    = 0;
        $presentDays = 0;
        $paidLeaves  = 0;
        $paidHolidays= 0;     // PH — paid holidays (Republic Day, Diwali, etc.)
        $usedSummary = false;

        if (\Illuminate\Support\Facades\Schema::hasTable('attendance_summary')) {
            $summary = \App\Models\AttendanceSummary::where('emp_id', $e->emp_id)
                ->where('period_year', $run->period_year)
                ->where('period_month', $run->period_month)
                ->first();
            if ($summary) {
                $presentDays  = (float) $summary->p_count;
                $paidLeaves   = $summary->totalLeaveDays() + (float) $summary->w_count;
                $absentDays   = (float) $summary->a_count;
                $halfDays     = (float) $summary->hd_count;
                $paidHolidays = (float) ($summary->ph_count ?? 0);
                $usedSummary  = true;
            }
        }

        // Detect whether this is a contract-worker salary group early — needed
        // for the unmarked-day handling below (different policy per category).
        $groupNameForCheck  = (string) ($e->salary_group->salary_group_name ?? '');
        $isWorkerForFallback = (bool) preg_match('/^\s*Cont(r?)a(r?)ctor\b/i', $groupNameForCheck);

        if (!$usedSummary) {
            $attendance = AttendanceDaily::where('emp_id', $e->emp_id)
                ->whereBetween('attn_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->keyBy(fn ($r) => Carbon::parse($r->attn_date)->toDateString());

            $unpaidStatuses = ['Absent', 'LOP', 'Unpaid Leave'];

            if ($attendance->count() > 0) {
                foreach ($attendance as $row) {
                    $s = (string) ($row->status ?? '');
                    if (in_array($s, $unpaidStatuses, true))      $absentDays++;
                    elseif ($s === 'Half Day')                    $halfDays++;
                    elseif ($s === 'Present' || $s === 'On Duty') $presentDays++;
                    elseif ($s !== '')                            $paidLeaves++;   // On Leave / Holiday / Weekly Off
                }
                // ── Unmarked-day handling ──
                //   Regular employees (monthly salary): unmarked → Present
                //     (legacy: salaried staff don't always track daily attendance)
                //   Contract workers (daily wage): unmarked → ABSENT
                //     (you must explicitly mark each Present day, otherwise no pay)
                $unmarked = max(0, $totalDays - $attendance->count());
                if ($isWorkerForFallback) {
                    $absentDays += $unmarked;     // strict — no inflated salary
                } else {
                    $presentDays += $unmarked;
                }
            } else {
                // No attendance entries at all
                if ($isWorkerForFallback) {
                    // No attendance for a worker → no pay (fail-safe)
                    $absentDays = $totalDays;
                } else {
                    // Legacy behavior for staff: full attendance assumed
                    $presentDays = $totalDays;
                }
            }
        }

        // ─── Determine wage model: contract worker vs regular employee ─────
        // Contract workers (employees in a "Contractor" salary group with type
        // Worker/Sub-Staff/Labour) are paid ONLY for actual present days.
        // Weekly Offs, CL, SL, PL are NOT paid — pure daily-wage model.
        // Regular employees (Staff, etc.) get paid for W/Off + paid leaves
        // (full-month-attendance assumption, prorated only for absent + HD/2).
        $isContractWorker = false;
        $groupName = (string) ($e->salary_group->salary_group_name ?? '');
        if (preg_match('/^\s*Cont(r?)a(r?)ctor\b/i', $groupName)) {
            // Either "Contractor X" or seeded typo "Contarctor X" → contract worker group
            $isContractWorker = true;
        }

        if ($isContractWorker) {
            // ── Contract worker daily-wage formula ───────────────────────
            //   Salary    = Basic + HRA + DA + Conv + Med + Spl  (sum)
            //   per_day   = Salary ÷ 26 (workable days)
            //   payable_days = Present + Paid Holidays + 0.5×Half-Days
            //   final_salary = per_day × payable_days
            //   ⇒ payRatio   = payable_days ÷ 26
            // PH (paid holiday — Republic Day, Diwali, etc.) is treated
            // exactly like Present for workers. W/Off and CL/SL/PL stay UNPAID.
            $payableDays   = $presentDays + $paidHolidays + ($halfDays * 0.5);
            $workableDays  = (int) config('hreasy.payroll.worker_divisor', 26);
            $payRatio      = $workableDays > 0 ? ($payableDays / $workableDays) : 0;
            // For payslip display, show LOP relative to the 26-day standard
            $lopDays       = max(0, $workableDays - $payableDays);
        } else {
            // ── Regular employee (Staff, Sub-Staff) — UNCHANGED ──────────
            // Monthly-salaried employees get paid everything except Absent
            // + 0.5×HD. PH is irrelevant here (their pay isn't day-based).
            $lopDays     = $absentDays + ($halfDays * 0.5);
            $payableDays = max(0, $totalDays - $lopDays);
            $payRatio    = $totalDays > 0 ? ($payableDays / $totalDays) : 1;
        }

        // Prorate every component
        $basic = round($basic * $payRatio, 2);
        $hra   = round($hra   * $payRatio, 2);
        $da    = round($da    * $payRatio, 2);
        $conv  = round((float) $conv * $payRatio, 2);
        $med   = round((float) $med  * $payRatio, 2);
        $spl   = round($spl   * $payRatio, 2);
        $gross = round($gross * $payRatio, 2);

        // === Lookup OT for record-keeping ONLY ===
        // OT is paid separately (outside the salary slip) per HR policy. The
        // amount is stored on the payslip for audit/reporting but is NEVER
        // added to gross_earnings, total_deductions, or net_pay.
        $otAmount = 0.0;
        if (\Illuminate\Support\Facades\Schema::hasTable('overtime_records')) {
            $ot = \App\Models\OvertimeRecord::where('emp_id', $e->emp_id)
                ->where('period_year',  $run->period_year)
                ->where('period_month', $run->period_month)
                ->first();
            if ($ot) {
                $otAmount = (float) $ot->ot_amount;
            }
        }

        // === Statutory (computed on PRORATED wages — EXCLUDING OT) ===
        // CoSS 2020 §2(88): pass full component breakdown so PF & ESI use
        // the unified wage definition with the 50% add-back proviso. Wages
        // for ESI/PF are NOT the same as gross when HRA + Conv + etc. exceed
        // half of total remuneration.
        // OT is intentionally excluded from this $gross because per HR policy
        // ESI/PF are not deducted on overtime — OT is paid in full as a top-up.
        $components = [
            'basic'   => $basic,
            'da'      => $da,
            'hra'     => (float) $hra,
            'conv'    => (float) $conv,
            'medical' => (float) $med,
            'spl'     => (float) $spl,
            'gross'   => $gross,
        ];
        // ── Per-employee statutory toggles (Manage Salary > Config flags) ──
        // Default behavior when flags are not set / column missing: applicable.
        // When HR explicitly unchecks PF/ESI/LWF on /payroll/manage-salary/{id}/config,
        // the corresponding deduction is set to ₹0 here.
        $pfApplicable  = !\Illuminate\Support\Facades\Schema::hasColumn('employees', 'pf_applicable_flag')
                         || ($e->pf_applicable_flag ?? true) == true;
        $esiApplicable = !\Illuminate\Support\Facades\Schema::hasColumn('employees', 'esi_applicable_flag')
                         || ($e->esi_applicable_flag ?? true) == true;
        $lwfApplicable = !\Illuminate\Support\Facades\Schema::hasColumn('employees', 'lwf_apply_flag')
                         || ($e->lwf_apply_flag ?? true) == true;

        $pf = $pfApplicable
            ? $this->pf->compute($components)
            : ['wage' => 0, 'employee' => 0, 'employer' => 0, 'eps' => 0, 'edli' => 0, 'admin' => 0];
        $esi = $esiApplicable
            ? $this->esi->compute($components)
            : ['eligible' => false, 'wage' => 0, 'employee' => 0, 'employer' => 0];
        // Default to RJ (Rajasthan — no PT, no LWF) when the employee record
        // doesn't specify a state. Override per-employee via the edit form.
        $pt  = $this->pt->compute($gross, $e->pt_state ?? 'RJ', $run->period_month);
        $lwf = $lwfApplicable
            ? $this->lwf->compute($e->lwf_state ?? 'RJ', $run->period_month)
            : ['employee' => 0, 'employer' => 0];
        // TDS — auto-computation DISABLED per HR policy. Defaults to 0 and is
        // only applied when manually entered via /payroll/salary-deductions/create
        // (handled in the manual_deductions block below).
        $tds = 0.0;
        $grat= $this->gratuity->provision($basic + $da);
        $bon = $this->bonus->monthlyProvision($basic + $da, $gross);

        // === OT is paid SEPARATELY (outside payslip) — do NOT add to gross.
        //     The $otAmount is stored on the payslip's ot_amount column purely
        //     for reference, but gross_earnings stays at the regular wage. ===

        // === Manual deductions (entered before payroll runs) ===
        // HR can pre-enter advances, loans, TDS overrides, VPF, mobile/canteen/etc.
        // via /payroll/salary-deductions/create. The engine applies them here.
        $advanceRecovery = 0.0;
        $loanEmi         = 0.0;
        $vpfAmount       = 0.0;  // Voluntary PF (extra employee EPF contribution)
        $postDeduction   = 0.0;  // sum of AG donation + maintenance + mobile + canteen + misc + rent
        if (\Illuminate\Support\Facades\Schema::hasTable('manual_deductions')) {
            $manual = \App\Models\ManualDeduction::where('emp_id', $e->emp_id)
                ->where('period_year', $run->period_year)
                ->where('period_month', $run->period_month)
                ->first();
            if ($manual) {
                $advanceRecovery = (float) $manual->advance_deduction;
                $loanEmi         = (float) $manual->loan_deduction;
                $postDeduction   = $manual->postDeductionTotal();
                if (\Illuminate\Support\Facades\Schema::hasColumn('manual_deductions','vpf_deduction')) {
                    $vpfAmount   = (float) ($manual->vpf_deduction ?? 0);
                }
                // TDS comes ONLY from manual entry now (auto-compute is disabled).
                // Use whatever HR entered, including 0.
                $tds = (float) ($manual->tds_deduction ?? 0);
            }
        }

        // === Aggregate ===
        $totalDed = $pf['employee'] + $esi['employee'] + $pt + $lwf['employee'] + $tds
                  + $vpfAmount
                  + $loanEmi + $advanceRecovery + $postDeduction;
        $netPay   = $gross - $totalDed;
        $ctc      = $gross + $pf['employer'] + $pf['eps'] + $pf['edli'] + $pf['admin']
                          + $esi['employer'] + $grat + $lwf['employer'];

        $payslipPayload = [
            'run_id'              => $run->run_id,
            'emp_id'              => $e->emp_id,
            'period_year'         => $run->period_year,
            'period_month'        => $run->period_month,
            'payable_days'        => $payableDays,
            'lop_days'            => $lopDays,
            'present_days'        => $presentDays,
            'basic'               => $basic,
            'hra'                 => $hra,
            'da'                  => $da,
            'conveyance'          => $conv,
            'medical'             => $med,
            'spl_allow'           => $spl,
            'ot_amount'           => $otAmount,
            'gross_earnings'      => $gross,
            'epf_emp'             => $pf['employee'],
            'esi_emp'             => $esi['employee'],
            'pt'                  => $pt,
            'lwf_emp'             => $lwf['employee'],
            'tds'                 => $tds,
            'bonus'               => $bon,
            'loan_emi'            => $loanEmi,
            'advance_recovery'    => $advanceRecovery,
            'post_deduction'      => $postDeduction,
            'total_deductions'    => $totalDed,
            'net_pay'             => $netPay,
            'employer_pf'         => $pf['employer'],
            'eps'                 => $pf['eps'],
            'edli'                => $pf['edli'],
            'pf_admin'            => $pf['admin'],
            'employer_esi'        => $esi['employer'],
            'gratuity_provision'  => $grat,
            'lwf_employer'        => $lwf['employer'],
            'total_employer_cost' => $ctc,
            'bank_id'             => $e->bank_id,
            'bank_account'        => $e->bank_account_no,
            'ifsc'                => $e->bank_ifsc,
            'disbursement_mode'   => $e->salary_disbursement_mode ?? 'NEFT',
            'disbursement_status' => 'Pending',
            'generated_at'        => now(),
        ];
        // Include VPF only if the column has been migrated
        if (\Illuminate\Support\Facades\Schema::hasColumn('payslips', 'vpf')) {
            $payslipPayload['vpf'] = $vpfAmount;
        }
        $payslip = Payslip::create($payslipPayload);

        // Backfill manual_deductions row with the new payslip_id (so future
        // joins / reports can link line items to their payslip)
        if (isset($manual) && $manual) {
            $manual->update(['payslip_id' => $payslip->payslip_id]);
        }

        return $payslip;
    }

    /**
     * Approve the run — moves Draft → Approved
     */
    public function approve(SalaryRun $run, string $approver): SalaryRun
    {
        $run->update([
            'status'             => 'Approved',
            'finance_approved_at'=> now(),
            'created_by'         => $approver,
        ]);
        return $run;
    }

    /**
     * Post (commit) the run, generates GL transactions
     */
    public function post(SalaryRun $run): SalaryRun
    {
        DB::transaction(function () use ($run) {
            // Generate GL salary_transactions per payslip
            // (simplified — extend for full GL posting)
            $run->update(['status'=>'Posted','posted_at'=>now()]);
        });
        return $run;
    }
}
