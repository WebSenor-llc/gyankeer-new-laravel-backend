<?php

namespace App\Services;

/**
 * Labour Welfare Fund — State-specific contributions.
 * Most states: half-yearly (June & December) or annual (December).
 */
class LWFCalculator
{
    public function compute(string $stateCode, int $month): array
    {
        $cfg = config("hreasy.lwf.$stateCode");
        if (!$cfg) return ['employee' => 0, 'employer' => 0, 'applicable' => false];

        $applicable = match ($cfg['frequency']) {
            'monthly'      => true,
            'half_yearly'  => in_array($month, $cfg['months'] ?? [6, 12]),
            'annual'       => in_array($month, $cfg['months'] ?? [12]),
            default        => false,
        };

        if (!$applicable) return ['employee' => 0, 'employer' => 0, 'applicable' => false];

        return [
            'applicable' => true,
            'employee'   => $cfg['employee'],
            'employer'   => $cfg['employer'],
        ];
    }
}
