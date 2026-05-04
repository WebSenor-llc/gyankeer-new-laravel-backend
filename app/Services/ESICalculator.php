<?php

namespace App\Services;

/**
 * Employee State Insurance — Code on Social Security 2020 §2(88)
 *
 * Wage definition (effective 21-Nov-2025, replaces ESI Act 1948):
 *   Wages = Basic + DA + Retaining Allowance + ADD-BACK
 *
 *   ADD-BACK = max(0, Excluded items − 50% of Total Remuneration)
 *
 *   Excluded items (per §2(88) sub-clauses a–i):
 *     bonus, house accommodation, PF contributions, conveyance, special expenses,
 *     HRA, tribunal awards, overtime, commission
 *
 * Eligibility: ESI Wages ≤ ₹21,000 (₹25,000 for PwD)
 * Rates:       Employee 0.75% · Employer 3.25%
 */
class ESICalculator
{
    /**
     * Backward-compatible wrapper:
     *  - If first arg is an array → new component-aware path
     *  - If first arg is a float  → legacy gross-based path (deprecated)
     */
    public function compute($grossOrComponents, bool $pwd = false): array
    {
        $components = is_array($grossOrComponents)
            ? $grossOrComponents
            : ['basic' => 0, 'da' => 0, 'gross' => (float) $grossOrComponents];

        $wages = $this->computeWages($components);
        $cap   = $pwd ? config('hreasy.esi.wage_cap_pwd', 25000)
                      : config('hreasy.esi.wage_cap', 21000);

        // §2(88) ceiling check on wages (NOT gross)
        if ($wages > $cap) {
            return ['employee' => 0, 'employer' => 0, 'eligible' => false, 'wage' => $wages];
        }

        return [
            'eligible' => true,
            'wage'     => $wages,
            'employee' => round($wages * config('hreasy.esi.employee_rate') / 100, 2),
            'employer' => round($wages * config('hreasy.esi.employer_rate') / 100, 2),
        ];
    }

    /**
     * Implements CoSS 2020 §2(88) wage calculation including the 50%
     * exclusion proviso. Pass an associative array of components.
     *
     * Required: 'basic' (float), 'da' (float)
     * Optional: 'retaining', 'hra', 'conv', 'medical', 'spl', 'bonus',
     *           'ot', 'commission', 'gross' (used only as a fallback
     *           if no exclusions array supplied)
     */
    public function computeWages(array $c): float
    {
        $basic     = (float) ($c['basic']     ?? 0);
        $da        = (float) ($c['da']        ?? 0);
        $retaining = (float) ($c['retaining'] ?? 0);
        $included  = $basic + $da + $retaining;

        // Sum of items that COSS 2020 excludes from wages
        $excluded = (float) ($c['hra']        ?? 0)
                  + (float) ($c['conv']       ?? 0)
                  + (float) ($c['medical']    ?? 0)
                  + (float) ($c['spl']        ?? 0)
                  + (float) ($c['bonus']      ?? 0)
                  + (float) ($c['ot']         ?? 0)
                  + (float) ($c['commission'] ?? 0);

        // If caller didn't pass a breakdown but did pass total gross, infer
        if ($excluded == 0 && isset($c['gross']) && (float) $c['gross'] > $included) {
            $excluded = (float) $c['gross'] - $included;
        }

        $totalRem = $included + $excluded;
        $halfRem  = $totalRem / 2;

        // 50% proviso — if excluded > half, add the excess to wages
        $addBack = max(0, $excluded - $halfRem);
        $wages   = $included + $addBack;

        return round($wages, 2);
    }
}
