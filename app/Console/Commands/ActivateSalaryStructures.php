<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeCareerEvent;
use App\Models\SalaryStructure;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mirror future-dated salary structures onto employees.current_* once the
 * effective_from date has arrived. Idempotent — safe to run repeatedly.
 *
 *   php artisan payroll:activate-structures
 *
 * Scheduled daily via routes/console.php.
 */
class ActivateSalaryStructures extends Command
{
    protected $signature = 'payroll:activate-structures {--asof= : Override "today" (YYYY-MM-DD) for testing}';
    protected $description = 'Mirror due future-dated salary structures onto employees.current_*';

    public function handle(): int
    {
        $today = $this->option('asof')
            ? Carbon::parse($this->option('asof'))->toDateString()
            : today()->toDateString();

        // Latest Active structure per employee whose effective_from <= today
        // AND employees.last_increment_date is older than effective_from
        // (i.e. not yet mirrored).
        $due = SalaryStructure::where('status', 'Active')
            ->where('effective_from', '<=', $today)
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('emp_id')
            ->map(fn($rows) => $rows->first());

        $activated = 0;
        foreach ($due as $empId => $s) {
            $emp = Employee::where('emp_id', $empId)->first();
            if (!$emp) continue;

            $effective = Carbon::parse($s->effective_from)->toDateString();
            $lastInc   = $emp->last_increment_date ? Carbon::parse($emp->last_increment_date)->toDateString() : null;

            if ($lastInc && $lastInc >= $effective) continue; // already mirrored

            $oldGross = (float) ($emp->current_gross ?? 0);
            $newGross = (float) ($s->basic + $s->hra + $s->da + $s->conveyance + $s->medical + $s->special_allowance);

            DB::transaction(function () use ($emp, $s, $effective, $oldGross, $newGross) {
                $emp->update([
                    'current_basic'            => (float) $s->basic,
                    'current_da'               => (float) $s->da,
                    'current_hra'              => (float) $s->hra,
                    'current_conv'             => (float) $s->conveyance,
                    'current_med'              => (float) $s->medical,
                    'current_spl'              => (float) $s->special_allowance,
                    'current_gross'            => $newGross,
                    'last_increment_date'      => $effective,
                    'last_increment_pct'       => $oldGross > 0 ? round(($newGross - $oldGross) / $oldGross * 100, 2) : 0,
                    'last_increment_old_gross' => $oldGross,
                    'last_increment_new_gross' => $newGross,
                ]);

                EmployeeCareerEvent::updateOrCreate(
                    ['emp_id' => $emp->emp_id, 'event_date' => $effective, 'event_type' => 'Increment'],
                    [
                        'old_value'    => sprintf('Gross %.2f', $oldGross),
                        'new_value'    => sprintf('Gross %.2f', $newGross),
                        'salary_old'   => $oldGross,
                        'salary_new'   => $newGross,
                        'hike_percent' => $oldGross > 0 ? round(($newGross - $oldGross) / $oldGross * 100, 2) : 0,
                        'remarks'      => $s->restructure_reason ?: 'Deferred activation (structure #' . $s->structure_id . ')',
                        'created_by'   => 'ActivateSalaryStructures',
                    ]
                );
            });

            $activated++;
            $this->line("Activated emp {$empId} → gross {$newGross} (effective {$effective})");
        }

        $this->info("Done. {$activated} structure(s) activated as of {$today}.");
        return self::SUCCESS;
    }
}
