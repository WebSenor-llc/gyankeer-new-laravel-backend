<?php

namespace App\Services;

/**
 * Payment of Gratuity Act 1972
 *
 * Formula: (Last Basic + DA) × 15 × Years / 26
 * Eligibility: 5 years continuous service · Tax-free cap ₹20 L u/s 10(10)
 */
class GratuityCalculator
{
    /**
     * Monthly provision based on (Basic + DA), at 4.81%.
     */
    public function provision(float $basicPlusDA): float
    {
        return round($basicPlusDA * config('hreasy.gratuity.rate', 4.81) / 100);
    }

    /**
     * Final gratuity payable on exit.
     *
     * @param  float $lastBasicDA   Last drawn Basic+DA monthly
     * @param  float $years         Years of continuous service
     * @return array['eligible','amount','tax_free','taxable']
     */
    public function payable(float $lastBasicDA, float $years): array
    {
        $minYears = config('hreasy.gratuity.min_years_eligibility', 5);
        $eligible = $years >= $minYears;

        if (!$eligible) {
            return ['eligible' => false, 'amount' => 0, 'tax_free' => 0, 'taxable' => 0];
        }

        $amount = $lastBasicDA * 15 * $years / config('hreasy.gratuity.days_per_month', 26);
        $cap    = config('hreasy.gratuity.tax_free_cap', 2000000);
        $taxFree= min($amount, $cap);
        $taxable= max(0, $amount - $cap);

        return [
            'eligible' => true,
            'amount'   => round($amount),
            'tax_free' => round($taxFree),
            'taxable'  => round($taxable),
        ];
    }
}
