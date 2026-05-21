<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generate a blank CSV template pre-filled with every active employee
 * (or one company / one salary group), so the user only needs to fill in
 * the cl/pl/sl numbers and re-import via leave:record-bulk.
 *
 * Usage:
 *     php artisan leave:bulk-template 2026 4
 *     php artisan leave:bulk-template 2026 4 --company=1
 *     php artisan leave:bulk-template 2026 4 --group=1036
 *     php artisan leave:bulk-template 2026 4 --out=database/data/april_2026_leaves.csv
 */
class LeaveBulkTemplate extends Command
{
    protected $signature = 'leave:bulk-template
        {year}
        {month}
        {--company=}
        {--group=}
        {--out=}';
    protected $description = 'Write a CSV template with every employee pre-filled (cl/pl/sl=0)';

    public function handle(): int
    {
        $year  = (int) $this->argument('year');
        $month = (int) $this->argument('month');
        $cid   = $this->option('company') ? (int) $this->option('company') : null;
        $gid   = $this->option('group')   ? (int) $this->option('group')   : null;
        $out   = $this->option('out') ?: base_path(sprintf('database/data/leaves-%d-%02d.csv', $year, $month));

        $q = \App\Models\Employee::with(['salary_group','company'])
            ->where('active_flag', true);
        if ($cid) $q->where('company_id', $cid);
        if ($gid) $q->where('salary_group_id', $gid);
        $emps = $q->orderBy('salary_group_id')->orderBy('emp_id')->get();

        if ($emps->isEmpty()) {
            $this->warn("No active employees match those filters.");
            return self::FAILURE;
        }

        @mkdir(dirname($out), 0755, true);
        $fh = fopen($out, 'w');
        // Extended header — name / group are advisory columns; bulk-importer
        // ignores them (case-insensitive map matches only emp_id/year/month/cl/pl/sl).
        fputcsv($fh, ['emp_id', 'name', 'salary_group', 'year', 'month', 'cl', 'pl', 'sl']);
        foreach ($emps as $e) {
            fputcsv($fh, [
                $e->emp_id,
                $e->full_name,
                optional($e->salary_group)->salary_group_name,
                $year, $month, 0, 0, 0,
            ]);
        }
        fclose($fh);

        $this->info("✅ Wrote " . $emps->count() . " row(s) to {$out}");
        $this->line("");
        $this->line("Next steps:");
        $this->line("  1. Open the CSV, fill in cl/pl/sl for employees who took leave");
        $this->line("  2. Save the file");
        $this->line("  3. php artisan leave:record-bulk \"{$out}\" --dry-run    # preview");
        $this->line("  4. php artisan leave:record-bulk \"{$out}\"              # apply");

        return self::SUCCESS;
    }
}
