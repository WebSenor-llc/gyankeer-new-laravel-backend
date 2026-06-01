<?php

namespace App\Console\Commands;

use App\Models\LeaveApplication;
use App\Services\LeaveAttendanceSync;
use Illuminate\Console\Command;

/**
 *   php artisan leave:sync-attendance                       # all non-rejected leaves
 *   php artisan leave:sync-attendance --year=2026 --month=5 # only leaves touching May 2026
 *   php artisan leave:sync-attendance --emp=123
 *   php artisan leave:sync-attendance --company=1
 *
 * Backfills existing leave_applications rows into attendance_daily +
 * attendance_summary (the Attendance by Group counts page). Use this for leaves
 * added before the auto-sync existed, e.g. last month's applications.
 *
 * Idempotent — LeaveAttendanceSync recomputes buckets from scratch, so running
 * this repeatedly never double-counts.
 */
class SyncLeaveAttendance extends Command
{
    protected $signature = 'leave:sync-attendance
                            {--year= : Only sync leaves overlapping this year}
                            {--month= : Only sync leaves overlapping this month (needs --year)}
                            {--company= : Limit to a company id}
                            {--emp= : Limit to a single emp_id}';

    protected $description = 'Backfill existing leave applications into attendance (daily + Attendance by Group).';

    public function handle(LeaveAttendanceSync $sync): int
    {
        $year    = $this->option('year') ? (int) $this->option('year') : null;
        $month   = $this->option('month') ? (int) $this->option('month') : null;
        $company = $this->option('company') ? (int) $this->option('company') : null;
        $emp     = $this->option('emp') ? (int) $this->option('emp') : null;

        $q = LeaveApplication::query()
            ->where('approval_status', '!=', 'Rejected')
            ->when($company, fn($x) => $x->where('company_id', $company))
            ->when($emp, fn($x) => $x->where('emp_id', $emp));

        // Restrict to leaves overlapping the requested period.
        if ($year && $month) {
            $start = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $end   = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
            $q->whereDate('from_date', '<=', $end)->whereDate('to_date', '>=', $start);
        } elseif ($year) {
            $q->whereYear('from_date', '<=', $year)->whereYear('to_date', '>=', $year);
        }

        $total = (clone $q)->count();
        if ($total === 0) {
            $this->warn('No matching leave applications found.');
            return self::SUCCESS;
        }

        $this->info("Syncing {$total} leave application(s) into attendance…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $ok = 0;
        $errors = [];
        $q->orderBy('leave_app_id')->chunkById(200, function ($leaves) use ($sync, &$ok, &$errors, $bar) {
            foreach ($leaves as $leave) {
                try {
                    $sync->apply($leave);
                    $ok++;
                } catch (\Throwable $e) {
                    $errors[] = "Leave #{$leave->leave_app_id} (emp {$leave->emp_id}): {$e->getMessage()}";
                }
                $bar->advance();
            }
        }, 'leave_app_id');

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Synced {$ok} of {$total} leave application(s).");

        if (!empty($errors)) {
            $this->error('Errors:');
            foreach (array_slice($errors, 0, 10) as $e) $this->line("  • {$e}");
            if (count($errors) > 10) $this->line('  … ' . (count($errors) - 10) . ' more.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
