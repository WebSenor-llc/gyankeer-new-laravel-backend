<?php

namespace App\Services;

/**
 * Profession Tax — State-wise slabs
 *
 * Reads slabs from config('hreasy.pt.<STATE_CODE>'). Maharashtra has
 * a Feb-special amount; some states levy half-yearly.
 */
class PTCalculator
{
    public function compute(float $gross, string $stateCode = 'MH', int $month = 1): float
    {
        $slabs = config("hreasy.pt.$stateCode", []);
        if (empty($slabs)) return 0;

        foreach ($slabs as $slab) {
            if ($gross >= $slab['min'] && $gross <= $slab['max']) {
                if (($stateCode === 'MH') && $month === 2 && isset($slab['feb_amount'])) {
                    return $slab['feb_amount'];
                }
                return $slab['amount'];
            }
        }
        return 0;
    }
}
