<?php

namespace Database\Seeders;

use App\Models\StatutorySlab;
use Illuminate\Database\Seeder;

/**
 * Seeds statutory rates / slabs from config/hreasy.php into the database.
 * Idempotent — uses updateOrCreate keyed by (category, key, fy_start_year).
 */
class StatutorySlabSeeder extends Seeder
{
    public function run(): void
    {
        $fy = 2025; // FY 2025-26

        // ---------- Single-value rates ----------
        $rates = [
            ['epf.employee_rate',  12.00, 'EPF — Employee Contribution Rate (%)'],
            ['epf.employer_rate',  3.67,  'EPF — Employer Contribution Rate (%)'],
            ['epf.eps_rate',       8.33,  'EPS Contribution Rate (%)'],
            ['epf.edli_rate',      0.50,  'EDLI Rate (%)'],
            ['epf.admin_rate',     0.50,  'EPF Admin Charges (%)'],
            ['epf.wage_cap',       15000, 'EPF Wage Cap (Monthly ₹)'],
            ['esi.employee_rate',  0.75,  'ESI — Employee Rate (%)'],
            ['esi.employer_rate',  3.25,  'ESI — Employer Rate (%)'],
            ['esi.wage_cap',       21000, 'ESI Wage Cap — Standard (₹)'],
            ['esi.wage_cap_pwd',   25000, 'ESI Wage Cap — PwD (₹)'],
            ['bonus.min_rate',     8.33,  'Statutory Bonus Min Rate (%)'],
            ['bonus.max_rate',     20.00, 'Statutory Bonus Max Rate (%)'],
            ['bonus.wage_cap',     7000,  'Bonus Wage Cap (₹/month)'],
            ['bonus.salary_cap',   21000, 'Bonus Eligibility Cap (₹/month)'],
            ['gratuity.rate',      4.81,  'Gratuity Provision Rate (%)'],
            ['gratuity.tax_free_cap', 2000000, 'Gratuity Tax-Free Cap (₹)'],
            ['tds.std_ded_new',    75000, 'Standard Deduction — New Regime (₹)'],
            ['tds.std_ded_old',    50000, 'Standard Deduction — Old Regime (₹)'],
            ['tds.cess_rate',      4.00,  'Health & Education Cess (%)'],
            ['tds.rebate_87a_new_limit',  1200000, '§87A New Regime — Income Limit (₹)'],
            ['tds.rebate_87a_new_amount',   60000, '§87A New Regime — Rebate Amount (₹)'],
            ['tds.rebate_87a_old_limit',   500000, '§87A Old Regime — Income Limit (₹)'],
            ['tds.rebate_87a_old_amount',   12500, '§87A Old Regime — Rebate Amount (₹)'],
        ];

        foreach ($rates as [$key, $value, $label]) {
            StatutorySlab::updateOrCreate(
                ['category' => 'rate', 'key' => $key, 'fy_start_year' => $fy],
                ['value_decimal' => $value, 'label' => $label, 'active_flag' => true]
            );
        }

        // ---------- PT slabs (state-wise) ----------
        foreach (config('hreasy.pt', []) as $state => $slabs) {
            if (empty($slabs)) continue;
            foreach ($slabs as $i => $slab) {
                StatutorySlab::updateOrCreate(
                    ['category' => 'pt', 'key' => "{$state}.slab.{$i}", 'fy_start_year' => $fy],
                    [
                        'value_decimal' => $slab['amount'] ?? 0,
                        'value_json'    => $slab,
                        'label'         => "PT — {$state} Slab " . ($i + 1) . " (₹" . ($slab['min'] ?? 0) . "–₹" . ($slab['max'] ?? 0) . ")",
                        'active_flag'   => true,
                    ]
                );
            }
        }

        // ---------- LWF (state-wise) ----------
        foreach (config('hreasy.lwf', []) as $state => $cfg) {
            StatutorySlab::updateOrCreate(
                ['category' => 'lwf', 'key' => "{$state}", 'fy_start_year' => $fy],
                [
                    'value_decimal' => ($cfg['employee'] ?? 0) + ($cfg['employer'] ?? 0),
                    'value_json'    => $cfg,
                    'label'         => "LWF — {$state} ({$cfg['frequency']})",
                    'active_flag'   => true,
                ]
            );
        }

        // ---------- TDS slabs (Old + New, FY 2025-26) ----------
        foreach (['new', 'old'] as $regime) {
            $cfgKey = "hreasy.tds.slabs_{$regime}_fy_2025_26";
            $slabs  = config($cfgKey, []);
            foreach ($slabs as $i => $slab) {
                StatutorySlab::updateOrCreate(
                    ['category' => 'tds', 'key' => "{$regime}.slab.{$i}", 'fy_start_year' => $fy],
                    [
                        'value_decimal' => $slab['rate'] ?? 0,
                        'value_json'    => $slab,
                        'label'         => "TDS — " . ucfirst($regime) . " Regime Slab " . ($i + 1) . " (₹" . number_format($slab['min'] ?? 0) . " – " . ($slab['max'] === PHP_INT_MAX ? '∞' : '₹' . number_format($slab['max'])) . " @ {$slab['rate']}%)",
                        'active_flag'   => true,
                    ]
                );
            }
        }

        $count = StatutorySlab::count();
        $this->command->info("Seeded {$count} statutory_slab rows (rate / pt / lwf / tds) for FY 2025-26.");
    }
}
