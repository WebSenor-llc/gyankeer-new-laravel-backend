<?php

namespace App\Services;

use App\Models\AttendanceDaily;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — keeps "Attendance by Group" in sync with leave applications.
 *
 * When an employee applies for leave, this writes the leave days into both
 * attendance_daily (per-date status) and attendance_summary (the per-month
 * counts that the Attendance by Group / Quick Counts page reads).
 *
 * Idempotent by design: the leave buckets (cl/sl/pl) for a month are RECOMPUTED
 * from every non-rejected leave application for that employee+month, never
 * incremented — so re-running, overlapping ranges, or duplicate calls never
 * double-count. Present (p_count) absorbs the slack so the row total always
 * equals days-in-month (the invariant enforced by countsSave()).
 */
class LeaveAttendanceSync
{
    /** Map a leave_code to an attendance_summary bucket key (cl|sl|pl). */
    private function bucket(?string $code): string
    {
        return match (strtoupper(substr(trim((string) $code), 0, 2))) {
            'CL' => 'cl',
            'SL' => 'sl',
            default => 'pl', // PL and any other code (EL/ML/…) fall back to Paid Leave
        };
    }

    /** Reflect a single leave application into attendance_daily + attendance_summary. */
    public function apply(LeaveApplication $app): void
    {
        $emp = Employee::find($app->emp_id);
        if (!$emp || !$app->from_date || !$app->to_date) return;

        $from = Carbon::parse($app->from_date)->startOfDay();
        $to   = Carbon::parse($app->to_date)->startOfDay();
        if ($to->lt($from)) return;

        $isHalf      = (bool) $app->half_day_flag && $from->isSameDay($to);
        $reasonLabel = strtoupper($this->bucket($app->leave_code)); // CL / SL / PL

        DB::transaction(function () use ($emp, $app, $from, $to, $isHalf, $reasonLabel) {
            // 1) per-date rows for the daily grid
            foreach (CarbonPeriod::create($from, $to) as $date) {
                AttendanceDaily::updateOrCreate(
                    ['emp_id' => $emp->emp_id, 'attn_date' => $date->toDateString()],
                    [
                        'company_id'      => $emp->company_id,
                        'employee_name'   => $emp->full_name,
                        'shift_id'        => $emp->shift_id,
                        'shift_name'      => $emp->shift->shift_name ?? null,
                        'status'          => $isHalf ? 'Half Day' : 'On Leave',
                        'status_reason'   => $isHalf ? ($reasonLabel . ' Half') : $reasonLabel,
                        'source'          => 'leave',
                        'approval_status' => $app->approval_status ?: 'Pending',
                    ]
                );
            }

            // 2) recompute the summary row for every month the leave touches
            $cursor = $from->copy()->startOfMonth();
            while ($cursor->lte($to)) {
                $this->recomputeSummary($emp, (int) $cursor->year, (int) $cursor->month);
                $cursor->addMonth();
            }
        });
    }

    /** Sum leave-day fractions per bucket from all non-rejected leave apps overlapping the month. */
    private function monthLeaveBuckets(int $empId, int $year, int $month): array
    {
        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $monthEnd   = $monthStart->copy()->endOfMonth();

        $buckets = ['cl' => 0.0, 'sl' => 0.0, 'pl' => 0.0];

        $apps = LeaveApplication::where('emp_id', $empId)
            ->where('approval_status', '!=', 'Rejected')
            ->whereDate('from_date', '<=', $monthEnd->toDateString())
            ->whereDate('to_date', '>=', $monthStart->toDateString())
            ->get();

        foreach ($apps as $a) {
            $f      = Carbon::parse($a->from_date)->startOfDay();
            $t      = Carbon::parse($a->to_date)->startOfDay();
            $isHalf = (bool) $a->half_day_flag && $f->isSameDay($t);
            $key    = $this->bucket($a->leave_code);
            foreach (CarbonPeriod::create($f, $t) as $d) {
                if ((int) $d->year !== $year || (int) $d->month !== $month) continue;
                $buckets[$key] += $isHalf ? 0.5 : 1.0;
            }
        }

        return $buckets;
    }

    /**
     * Rebuild the attendance_summary row for emp+month: leave buckets come from the
     * leave applications; W/A/HD/PH/OT are preserved from any existing row (so manual
     * counts-page entries aren't clobbered); Present is the remaining balance.
     */
    private function recomputeSummary(Employee $emp, int $year, int $month): void
    {
        if (!Schema::hasTable('attendance_summary')) return;

        $totalDays = (int) Carbon::createFromDate($year, $month, 1)->daysInMonth;

        // Default weekly-off count = occurrences of this employee's off weekday
        // (employees.weekly_off_pattern; blank => Sunday).
        $weeklyOff = \App\Support\WeeklyOff::countInMonth($emp->weekly_off_pattern, $year, $month);

        $hasPh = Schema::hasColumn('attendance_summary', 'ph_count');

        $existing = AttendanceSummary::where('emp_id', $emp->emp_id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        // Preserve non-leave buckets; default to the standard month pattern when new.
        $w  = $existing ? (float) $existing->w_count  : (float) $weeklyOff;
        $a  = $existing ? (float) $existing->a_count  : 0.0;
        $hd = $existing ? (float) $existing->hd_count : 0.0;
        $ph = $existing && $hasPh ? (float) $existing->ph_count : 0.0;
        $ot = $existing ? (float) $existing->ot_hours : 0.0;

        $lb = $this->monthLeaveBuckets($emp->emp_id, $year, $month);

        $nonPresent = $w + $lb['cl'] + $lb['sl'] + $lb['pl'] + $a + $hd + $ph;
        $p = max(0, round($totalDays - $nonPresent, 2));

        $payload = [
            'company_id'         => $emp->company_id,
            'p_count'            => $p,
            'w_count'            => $w,
            'cl_count'           => $lb['cl'],
            'sl_count'           => $lb['sl'],
            'pl_count'           => $lb['pl'],
            'a_count'            => $a,
            'hd_count'           => $hd,
            'ot_hours'           => $ot,
            'total_days'         => round($p + $nonPresent, 2),
            'created_by_user_id' => auth()->id(),
        ];
        if ($hasPh) $payload['ph_count'] = $ph;

        AttendanceSummary::updateOrCreate(
            ['emp_id' => $emp->emp_id, 'period_year' => $year, 'period_month' => $month],
            $payload
        );
    }
}
