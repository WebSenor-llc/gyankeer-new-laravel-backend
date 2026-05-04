<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Sets pt_state and lwf_state to 'RJ' (Rajasthan) for every active employee.
 *
 * Rajasthan does not levy Profession Tax — the engine was previously using
 * the default 'MH' fallback and incorrectly deducting ₹200/month per employee.
 * It also has no LWF (currently anyway).
 *
 * After running this seeder, the engine returns ₹0 for both PT and LWF for
 * all 472 employees because:
 *   - config('hreasy.pt.RJ')  is []   → PTCalculator returns 0
 *   - config('hreasy.lwf.RJ') is unset → LWFCalculator returns 0
 *
 * Idempotent — only updates rows where the field is null/empty/different.
 *
 * Run:  php artisan db:seed --class=SetEmployeeStateRJSeeder
 */
class SetEmployeeStateRJSeeder extends Seeder
{
    public function run(): void
    {
        $updated = Employee::whereNull('pt_state')
            ->orWhere('pt_state', '!=', 'RJ')
            ->orWhereNull('lwf_state')
            ->orWhere('lwf_state', '!=', 'RJ')
            ->update(['pt_state' => 'RJ', 'lwf_state' => 'RJ']);

        $this->command->info("Set pt_state = 'RJ' and lwf_state = 'RJ' on {$updated} employees.");
        $this->command->info("Recompute any existing salary runs to apply (the engine reads these per-employee states at compute time).");
    }
}
