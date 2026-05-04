<?php

namespace App\Services;

/**
 * Payment of Bonus Act 1965
 *
 * Eligible: salary ≤ ₹21,000 / month
 * Bonus wage = min(Basic+DA, ₹7,000) — capped per state min wage
 * Range: 8.33% (min) to 20% (max)
 */
class BonusCalculator
{
    public function isEligible(float $monthlyGross): bool
    {
        return $monthlyGross <= config('hreasy.bonus.salary_cap', 21000);
    }

    /**
     * Annual bonus payable.
     */
    public function annual(float $basicPlusDA, float $monthlyGross, ?float $rate = null): float
    {
        if (!$this->isEligible($monthlyGross)) return 0;

        $cap = config('hreasy.bonus.wage_cap', 7000);
        $wage = min($basicPlusDA, $cap);
        $rate = $rate ?? config('hreasy.bonus.min_rate', 8.33);

        return round($wage * 12 * $rate / 100);
    }

    /**
     * Monthly provision (1/12 of annual).
     */
    public function monthlyProvision(float $basicPlusDA, float $monthlyGross): float
    {
        return round($this->annual($basicPlusDA, $monthlyGross) / 12);
    }
}
