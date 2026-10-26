<?php

namespace App\Console\Commands;

use App\Models\SalaryComponent;
use App\Models\StatutorySlab;
use Illuminate\Console\Command;

/**
 * php artisan hreasy:resync-formulas [--period=YYYY-MM]
 *
 * Rewrites the display-only `formula` strings on `salary_components`
 * (EPF/EPS/EDLI/Admin) using the PF wage cap currently in force. Run on
 * ceiling-change cutover (e.g. 01-Oct-2026, EPFO 2026 → ₹25,000) so the
 * Payroll Components UI shows the right MIN(BASIC+DA, cap).
 *
 * NB: PFCalculator reads the ceiling live from statutory_slabs → config;
 * these formulas are documentation only. This command keeps the UI in sync
 * with reality — never rely on `config:cache` for that.
 */
class ResyncFormulas extends Command
{
    protected $signature = 'hreasy:resync-formulas {--period= : Period YYYY-MM (defaults to current)}';
    protected $description = 'Rewrite PF/EPS/EDLI/Admin formula strings on salary_components with current PF wage cap.';

    public function handle(): int
    {
        $period = $this->option('period') ?: date('Y-m');
        $cap    = StatutorySlab::rateAt('epf.wage_cap', $period)
                  ?? (float) config('hreasy.pf.wage_cap', 25000);
        $cap    = (int) $cap;

        $targets = [
            'DED-EPF' => '12%*MIN(BASIC+DA,%d)',
            'ER-EPF'  => '3.67%*MIN(BASIC+DA,%d)',
            'ER-EPS'  => '8.33%*MIN(BASIC+DA,%d)',
            'ER-EDLI' => '0.5%*MIN(BASIC+DA,%d)',
            'ER-ADM'  => '0.5%*MIN(BASIC+DA,%d)',
        ];

        $this->info("Resyncing formulas for period {$period} using cap ₹{$cap}");
        $updated = 0;
        foreach ($targets as $code => $fmt) {
            $c = SalaryComponent::where('component_code', $code)->first();
            if (!$c) {
                $this->warn("  {$code} not found — skipped");
                continue;
            }
            $new = sprintf($fmt, $cap);
            if ($c->formula === $new) {
                $this->line("  {$code}: already current ({$new})");
                continue;
            }
            $c->update(['formula' => $new]);
            $this->line("  {$code}: {$new}");
            $updated++;
        }
        $this->info("Done. {$updated} formula(s) updated.");
        return self::SUCCESS;
    }
}
