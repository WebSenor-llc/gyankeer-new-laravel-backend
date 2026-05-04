<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;

/**
 * Exports a CSV comparing pre-CoSS vs CoSS 2020 ESI calculation per employee.
 *
 *   php artisan payroll:esi-comparison
 *   php artisan payroll:esi-comparison --company=1
 *   php artisan payroll:esi-comparison --out=storage/app/esi-compare.csv
 */
class ExportEsiComparison extends Command
{
    protected $signature = 'payroll:esi-comparison
                            {--company= : Company id filter}
                            {--out= : Output path (default storage/app/esi-comparison.csv)}';

    protected $description = 'Export old vs new ESI per employee as CSV.';

    public function handle(): int
    {
        $cid  = (int) ($this->option('company') ?: 0);
        $path = $this->option('out') ?: storage_path('app/esi-comparison.csv');

        $q = Employee::where('active_flag', true);
        if ($cid) $q->where('company_id', $cid);
        $employees = $q->orderBy('emp_id')->get();

        $this->info("Exporting {$employees->count()} active employees to {$path}");

        $fp = fopen($path, 'w');
        fputs($fp, "\xEF\xBB\xBF");  // BOM for Excel
        fputcsv($fp, [
            'Emp ID', 'Name', 'Group', 'Type',
            'Basic', 'DA', 'HRA', 'Conv', 'Med', 'Spl', 'Gross',
            'Old ESI Eligible', 'Old ESI Wage', 'Old Emp ESI', 'Old Er ESI',
            'New CoSS Wages', 'Add-back', 'New ESI Eligible', 'New Emp ESI', 'New Er ESI',
            'Δ Emp ESI', 'Δ Er ESI', 'Δ Net Pay (employee gain)',
        ]);

        $cap = config('hreasy.esi.wage_cap', 21000);
        $totals = ['old_emp'=>0,'new_emp'=>0,'old_er'=>0,'new_er'=>0];

        foreach ($employees as $e) {
            $basic = (float) $e->current_basic;
            $da    = (float) $e->current_da;
            $hra   = (float) $e->current_hra;
            $conv  = is_numeric($e->current_conv) ? (float) $e->current_conv : 0;
            $med   = is_numeric($e->current_med)  ? (float) $e->current_med  : 0;
            $spl   = (float) $e->current_spl;
            $gross = (float) $e->current_gross ?: ($basic + $da + $hra + $conv + $med + $spl);

            // OLD: ESI on full gross
            $oldEligible = $gross <= $cap;
            $oldEmp = $oldEligible ? round($gross * 0.0075, 2) : 0;
            $oldEr  = $oldEligible ? round($gross * 0.0325, 2) : 0;

            // NEW CoSS 2020: wages = Basic+DA + add-back
            $excluded = $hra + $conv + $med + $spl;
            $half     = $gross / 2;
            $addback  = max(0, $excluded - $half);
            $cossWages = $basic + $da + $addback;
            $newEligible = $cossWages <= $cap;
            $newEmp = $newEligible ? round($cossWages * 0.0075, 2) : 0;
            $newEr  = $newEligible ? round($cossWages * 0.0325, 2) : 0;

            fputcsv($fp, [
                $e->emp_id, $e->full_name,
                $e->salary_group->salary_group_name ?? '—',
                $e->employee_type ?? '',
                number_format($basic, 2, '.', ''),
                number_format($da, 2, '.', ''),
                number_format($hra, 2, '.', ''),
                number_format($conv, 2, '.', ''),
                number_format($med, 2, '.', ''),
                number_format($spl, 2, '.', ''),
                number_format($gross, 2, '.', ''),
                $oldEligible ? 'Yes' : 'No',
                number_format($oldEligible ? $gross : 0, 2, '.', ''),
                number_format($oldEmp, 2, '.', ''),
                number_format($oldEr, 2, '.', ''),
                number_format($cossWages, 2, '.', ''),
                number_format($addback, 2, '.', ''),
                $newEligible ? 'Yes' : 'No',
                number_format($newEmp, 2, '.', ''),
                number_format($newEr, 2, '.', ''),
                number_format($newEmp - $oldEmp, 2, '.', ''),
                number_format($newEr  - $oldEr, 2, '.', ''),
                number_format($oldEmp - $newEmp, 2, '.', ''),  // employee gains when ESI drops
            ]);

            $totals['old_emp'] += $oldEmp;
            $totals['new_emp'] += $newEmp;
            $totals['old_er']  += $oldEr;
            $totals['new_er']  += $newEr;
        }
        fclose($fp);

        $this->info("✓ CSV written: {$path}");
        $this->table(
            ['Metric', 'OLD', 'NEW', 'Δ'],
            [
                ['Total Employee ESI', '₹' . number_format($totals['old_emp'], 2), '₹' . number_format($totals['new_emp'], 2), '₹' . number_format($totals['new_emp'] - $totals['old_emp'], 2)],
                ['Total Employer ESI', '₹' . number_format($totals['old_er'], 2),  '₹' . number_format($totals['new_er'], 2),  '₹' . number_format($totals['new_er']  - $totals['old_er'], 2)],
                ['Total Combined',     '₹' . number_format($totals['old_emp'] + $totals['old_er'], 2),
                                        '₹' . number_format($totals['new_emp'] + $totals['new_er'], 2),
                                        '₹' . number_format(($totals['new_emp'] + $totals['new_er']) - ($totals['old_emp'] + $totals['old_er']), 2)],
            ]
        );
        return self::SUCCESS;
    }
}
