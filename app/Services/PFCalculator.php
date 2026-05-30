<?php

namespace App\Services;

/**
 * EPF & Miscellaneous Provisions Act 1952 — Hreasy company-specific rule
 *
 * PF Wage = Basic + DA + Conveyance + Medical + Special Allowance
 *           (i.e. EVERYTHING in the salary structure EXCEPT HRA)
 * PF Wage capped at ₹15,000
 *
 * Computes Employee + Employer + EPS + EDLI + Admin charges.
 *
 * For ESI, the legal CoSS 2020 §2(88) wage definition still applies — see
 * ESICalculator::computeWages(). PF intentionally does NOT use that here.
 */
class PFCalculator
{
    /**
     * Compute the PF wage per the company's rule:
     *   wage = Basic + DA + Conveyance + Medical + Special Allowance
     *         (HRA excluded, as it sits outside the PF base)
     *   wage capped at ₹15,000.
     *
     * Accepts either:
     *  - array of components (preferred): keys basic, da, conv, medical, spl
     *  - float (legacy callers): treats the value as Basic+DA only
     */
    public function pfWage($components): float
    {
        if (!is_array($components)) {
            $wage = (float) $components;            // legacy: Basic+DA only
        } else {
            $wage = (float) ($components['basic']   ?? 0)
                  + (float) ($components['da']      ?? 0)
                  + (float) ($components['conv']    ?? 0)
                  + (float) ($components['medical'] ?? 0)
                  + (float) ($components['spl']     ?? 0);
        }
        $cap = config('hreasy.pf.wage_cap', 15000);
        return round(min($wage, $cap), 2);
    }

    /**
     * Compute all five PF buckets on the company's PF wage.
     *
     * EPFO convention for the employer share (A/C 1):
     *     ER = Employee Total (12%)  −  EPS (8.33%)
     * NOT  ER = round(3.67% × wage)
     *
     * This matters at the cap: 15000 × 3.67% = 550.50 → rounds to 551 if you
     * compute it independently, but the EPFO portal expects 1800 − 1250 = 550.
     * Computing it as the difference also guarantees EE always exactly equals
     * EPS + ER on the ECR, which the EPFO upload validates.
     */
    public function compute($basicPlusDAOrComponents): array
    {
        $wage = $this->pfWage($basicPlusDAOrComponents);

        // EPFO ECR submissions are in WHOLE RUPEES. So we round to 0 decimals
        // here, not 2. Otherwise EPS=1249.50 + ER=550.50 = 1800.00 looks like
        // EPS=1250 + ER=551 = 1801 on the printed challan (half-up rounding of
        // each display cell), which the EPFO portal rejects on upload.
        //
        // Rules in order:
        //   employee = round(12%  x wage)            -- 1800 at the 15k cap
        //   eps      = round(8.33% x wage)           -- 1250 at the 15k cap (1249.50 -> 1250)
        //   employer = employee - eps                -- 550 at the 15k cap   (NOT a fresh 3.67%)
        //
        // This guarantees EE == EPS + ER in every row, matching what EPFO
        // validates on ECR upload.
        $employee = (int) round($wage * config('hreasy.pf.employee_rate') / 100);
        $eps      = (int) round($wage * config('hreasy.pf.eps_rate')      / 100);
        $employer = $employee - $eps;

        return [
            'wage'     => $wage,
            'employee' => $employee,
            'employer' => $employer,
            'eps'      => $eps,
            'edli'     => (int) round($wage * config('hreasy.pf.edli_rate')  / 100),
            'admin'    => (int) round($wage * config('hreasy.pf.admin_rate') / 100),
        ];
    }

    /**
     * Generate ECR row for an employee for a period
     */
    public function ecrRow(string $uan, string $name, float $grossWage, float $basicPlusDA, int $ncpDays = 0): array
    {
        $pf = $this->compute($basicPlusDA);
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
