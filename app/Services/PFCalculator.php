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
     */
    public function compute($basicPlusDAOrComponents): array
    {
        $wage = $this->pfWage($basicPlusDAOrComponents);

        return [
            'wage'     => $wage,
            'employee' => round($wage * config('hreasy.pf.employee_rate') / 100, 2),
            'employer' => round($wage * config('hreasy.pf.employer_rate') / 100, 2),
            'eps'      => round($wage * config('hreasy.pf.eps_rate') / 100, 2),
            'edli'     => round($wage * config('hreasy.pf.edli_rate') / 100, 2),
            'admin'    => round($wage * config('hreasy.pf.admin_rate') / 100, 2),
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
