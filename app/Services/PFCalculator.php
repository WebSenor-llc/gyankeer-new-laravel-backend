<?php

namespace App\Services;

use App\Models\StatutorySlab;

/**
 * EPF & Miscellaneous Provisions Act 1952 — Hreasy company-specific rule
 *
 * PF Wage = Basic + DA + Conveyance + Medical + Special Allowance
 *           (i.e. EVERYTHING in the salary structure EXCEPT HRA)
 * Wage ceiling: ₹15,000 up to Sep 2026, ₹25,000 w.e.f. 01-Oct-2026 (EPFO 2026).
 * Resolved per period from `statutory_slabs` with config fallback.
 *
 * Computes Employee + Employer + EPS + EDLI + Admin charges.
 *
 * For ESI, the legal CoSS 2020 §2(88) wage definition still applies — see
 * ESICalculator::computeWages(). PF intentionally does NOT use that here.
 */
class PFCalculator
{
    /**
     * Resolve the applicable PF wage ceiling for the given period.
     * Optionally prorate by worked days (for mid-month joiners / exits).
     */
    public function ceiling(?string $period = null, int $workedDays = 0, int $monthDays = 0): float
    {
        $period = $period ?: date('Y-m');
        $cap = StatutorySlab::rateAt('epf.wage_cap', $period)
             ?? (float) config('hreasy.pf.wage_cap', 25000);

        if ($workedDays > 0 && $monthDays > 0 && $workedDays < $monthDays) {
            $cap = round($cap * $workedDays / $monthDays, 2);
        }
        return (float) $cap;
    }

    /**
     * Compute the PF wage per the company's rule:
     *   wage = Basic + DA + Conveyance + Medical + Special Allowance
     * International Workers (iwFlag=true) have NO cap.
     *
     * Accepts either:
     *  - array of components (preferred): keys basic, da, conv, medical, spl
     *  - float (legacy callers): treats the value as Basic+DA only
     */
    public function pfWage($components, ?string $period = null, bool $iwFlag = false, int $workedDays = 0, int $monthDays = 0): float
    {
        if (!is_array($components)) {
            $wage = (float) $components;
        } else {
            $wage = (float) ($components['basic']   ?? 0)
                  + (float) ($components['da']      ?? 0)
                  + (float) ($components['conv']    ?? 0)
                  + (float) ($components['medical'] ?? 0)
                  + (float) ($components['spl']     ?? 0);
        }

        if ($iwFlag) {
            // International Workers: no wage cap applies.
            return round($wage, 2);
        }

        $cap = $this->ceiling($period, $workedDays, $monthDays);
        return round(min($wage, $cap), 2);
    }

    /**
     * Compute all five PF buckets on the company's PF wage.
     *
     * EPFO convention for the employer share (A/C 1):
     *     ER = Employee Total (12%)  −  EPS (8.33%)
     * NOT  ER = round(3.67% × wage)
     *
     * At the 25k cap: 25000 × 3.67% = 917.50 → rounds to 918 if computed
     * independently, but EPFO portal expects 3000 − 2083 = 917.
     * Computing it as the difference also guarantees EE always exactly equals
     * EPS + ER on the ECR, which the EPFO upload validates.
     *
     * Signature kept backward-compatible: legacy callers pass just components;
     * new callers additionally pass $period / $iwFlag / worked-day proration.
     */
    public function compute(
        $basicPlusDAOrComponents,
        ?string $period = null,
        bool $iwFlag = false,
        int $workedDays = 0,
        int $monthDays = 0
    ): array {
        $wage = $this->pfWage($basicPlusDAOrComponents, $period, $iwFlag, $workedDays, $monthDays);
        $capSnapshot = $iwFlag
            ? 0.0                                                // IW → no cap recorded
            : $this->ceiling($period, $workedDays, $monthDays);

        // EPFO ECR submissions are in WHOLE RUPEES. See PR notes on rounding
        // invariant EE == EPS + ER (EPFO rejects otherwise).
        $employee = (int) round($wage * config('hreasy.pf.employee_rate') / 100);
        $eps      = (int) round($wage * config('hreasy.pf.eps_rate')      / 100);
        $employer = $employee - $eps;

        return [
            'wage'          => $wage,
            'employee'      => $employee,
            'employer'      => $employer,
            'eps'           => $eps,
            'edli'          => (int) round($wage * config('hreasy.pf.edli_rate')  / 100),
            'admin'         => (int) round($wage * config('hreasy.pf.admin_rate') / 100),
            'cap_snapshot'  => $capSnapshot,
        ];
    }

    /**
     * Generate ECR row for an employee for a period
     */
    public function ecrRow(string $uan, string $name, float $grossWage, float $basicPlusDA, int $ncpDays = 0, ?string $period = null, bool $iwFlag = false): array
    {
        $pf = $this->compute($basicPlusDA, $period, $iwFlag);
        return [
            'uan'              => $uan,
            'member_name'      => $name,
            'gross_wage'       => $grossWage,
            'epf_wage_capped'  => $pf['wage'],
            'eps_wage_capped'  => $pf['wage'],
            'edli_wage_capped' => $pf['wage'],
            'ee_share_12pct'   => $pf['employee'],
            'eps_8_33'         => $pf['eps'],
            'er_share_3_67'    => $pf['employer'],
            'edli_0_5'         => $pf['edli'],
            'pf_admin_0_5'     => $pf['admin'],
            'ncp_days'         => $ncpDays,
            'refund_member'    => 0,
            'lop_amount'       => 0,
        ];
    }
}
