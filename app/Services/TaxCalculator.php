<?php

namespace App\Services;

use App\Models\Employee;

/**
 * Income Tax §192 — TDS calculator
 *
 * Supports both Old and New (§115BAC) regimes for FY 2025-26.
 * Annual computation with proration to monthly TDS.
 */
class TaxCalculator
{
    /**
     * Compute total annual tax (income tax + cess) for an employee.
     *
     * @param  float    $monthlyGross
     * @param  string   $regime  'New' or 'Old'
     * @param  Employee $emp     Used to read declared 80C/80D etc.
     * @return float
     */
    public function annualTax(float $monthlyGross, string $regime, Employee $emp): float
    {
        $annualGross = $monthlyGross * 12;

        $stdDed = $regime === 'Old'
            ? config('hreasy.tds.standard_deduction_old', 50000)
            : config('hreasy.tds.standard_deduction_new', 75000);

        $taxable = max(0, $annualGross - $stdDed);

        if ($regime === 'Old') {
            // §80C, §80D etc. only allowed under Old regime
            $taxable = max(0, $taxable
                - min(150000,  (float)($emp->sec_80c_declared    ?? 0))
                - (float)($emp->sec_80d_declared    ?? 0)
                - min(50000,   (float)($emp->sec_80ccd1b_declared?? 0))
                - (float)($emp->sec_80e_declared    ?? 0)
                - (float)($emp->sec_80g_declared    ?? 0)
                - min(200000,  (float)($emp->sec_24b_declared    ?? 0))
                - $this->hraExemption($emp, $monthlyGross)
            );
        }

        // Normalise the regime to 'new' or 'old' (DB sometimes has 'New', 'old', '0', etc.)
        $reg = strtolower(trim((string) $regime));
        if (!in_array($reg, ['new', 'old'])) {
            $reg = 'new';   // default to New regime
        }
        $slabs = config('hreasy.tds.slabs_' . $reg . '_fy_2025_26', $this->fallbackSlabs($reg));
        $tax   = $this->applySlabs($taxable, is_array($slabs) ? $slabs : $this->fallbackSlabs($reg));

        // §87A rebate
        $rebate = $this->rebate87A($regime, $taxable, $tax);
        $tax = max(0, $tax - $rebate);

        // Surcharge for high incomes
        $tax += $this->surcharge($tax, $taxable);

        // 4% Health & Education Cess
        $cess = $tax * config('hreasy.tds.cess_rate', 4) / 100;

        return round($tax + $cess);
    }

    public function monthlyTDS(float $monthlyGross, string $regime, Employee $emp): float
    {
        return round($this->annualTax($monthlyGross, $regime, $emp) / 12);
    }

    /**
     * Hardcoded fallback slabs used if config('hreasy.tds.*') hasn't been
     * loaded (e.g. stale config cache). Matches FY 2025-26 §192 slabs.
     */
    private function fallbackSlabs(string $regime): array
    {
        if ($regime === 'old') {
            return [
                ['min' => 0,       'max' => 250000,      'rate' => 0],
                ['min' => 250001,  'max' => 500000,      'rate' => 5],
                ['min' => 500001,  'max' => 1000000,     'rate' => 20],
                ['min' => 1000001, 'max' => PHP_INT_MAX, 'rate' => 30],
            ];
        }
        // New regime FY 2025-26
        return [
            ['min' => 0,        'max' => 400000,      'rate' => 0],
            ['min' => 400001,   'max' => 800000,      'rate' => 5],
            ['min' => 800001,   'max' => 1200000,     'rate' => 10],
            ['min' => 1200001,  'max' => 1600000,     'rate' => 15],
            ['min' => 1600001,  'max' => 2000000,     'rate' => 20],
            ['min' => 2000001,  'max' => 2400000,     'rate' => 25],
            ['min' => 2400001,  'max' => PHP_INT_MAX, 'rate' => 30],
        ];
    }

    private function applySlabs(float $taxable, array $slabs): float
    {
        $tax  = 0;
        $prev = 0;
        foreach ($slabs as $slab) {
            if ($taxable <= $slab['max']) {
                $tax += ($taxable - $prev) * $slab['rate'] / 100;
                return $tax;
            }
            $tax += ($slab['max'] - $prev) * $slab['rate'] / 100;
            $prev = $slab['max'];
        }
        return $tax;
    }

    private function rebate87A(string $regime, float $taxable, float $tax): float
    {
        $reg = strtolower(trim((string) $regime));
        if ($reg !== 'old' && $reg !== 'new') $reg = 'new';

        if ($reg === 'new') {
            $limit  = (float) (config('hreasy.tds.rebate_87a_new_limit',  1200000));
            $amount = (float) (config('hreasy.tds.rebate_87a_new_amount',   60000));
            return $taxable <= $limit ? min($tax, $amount) : 0;
        }
        $limit  = (float) (config('hreasy.tds.rebate_87a_old_limit',  500000));
        $amount = (float) (config('hreasy.tds.rebate_87a_old_amount',  12500));
        return $taxable <= $limit ? min($tax, $amount) : 0;
    }

    private function surcharge(float $tax, float $taxable): float
    {
        $thresholds = config('hreasy.tds.surcharge_thresholds', []);
        if (!is_array($thresholds)) $thresholds = [];
        foreach ($thresholds as $sl) {
            if (!is_array($sl)) continue;
            $min  = (float) ($sl['min']  ?? 0);
            $max  = (float) ($sl['max']  ?? PHP_INT_MAX);
            $rate = (float) ($sl['rate'] ?? 0);
            if ($taxable >= $min && $taxable <= $max) {
                return $tax * $rate / 100;
            }
        }
        return 0;
    }

    /**
     * HRA exemption under §10(13A) — minimum of:
     *   (a) Actual HRA received
     *   (b) Rent paid - 10% of (Basic + DA)
     *   (c) 50% of (Basic+DA) for metros, 40% otherwise
     */
    private function hraExemption(Employee $emp, float $monthlyGross): float
    {
        $hraReceived = ($emp->current_hra ?? 0) * 12;
        $rentPaid    = (float)($emp->hra_rent_paid_annual ?? 0);
        if ($rentPaid <= 0 || $hraReceived <= 0) return 0;

        $basicDA = (($emp->current_basic ?? 0) + ($emp->current_da ?? 0)) * 12;
        $metro   = in_array(strtolower($emp->mailing_city ?? ''), ['mumbai','delhi','kolkata','chennai']);
        $cityCap = $basicDA * ($metro ? 0.50 : 0.40);

        return min($hraReceived, max(0, $rentPaid - 0.10 * $basicDA), $cityCap);
    }
}
