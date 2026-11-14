<?php

namespace Tests\Unit;

use App\Services\PFCalculator;
use Tests\TestCase;

class PFCalculatorFpfTest extends TestCase
{
    public function test_without_eps_routes_full_employer_share_to_epf(): void
    {
        $calc = app(PFCalculator::class);
        $pf = $calc->withoutEps([
            'wage' => 15000, 'employee' => 1800, 'employer' => 551, 'eps' => 1249,
            'edli' => 75, 'admin' => 75, 'cap_snapshot' => 25000,
        ]);

        $this->assertSame(0, $pf['eps']);
        $this->assertSame(1800, $pf['employer']);
        $this->assertSame(1800, $pf['employee']);
        $this->assertSame($pf['employee'], $pf['eps'] + $pf['employer']); // EPFO EE == EPS + ER
        $this->assertSame(75, $pf['edli']);
    }
}
