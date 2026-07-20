<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;

/**
 * Hreasy by WebSenor — single source of truth for "how much leave is left".
 *
 * leave_balances.closing_balance only moves at payroll-compute time (via
 * leave_ledger, see PayrollEngine::…). Leave applied this month but not yet
 * payrolled is therefore NOT reflected in closing_balance — so a naive check
 * would let the same 5 CL be applied twice.
 *
 *     appliedInFy = SUM(leave_applications.days) for the FY (Pending+Approved)
 *     unledgered  = max(0, appliedInFy - availed_ytd)
 *     available   = closing_balance - unledgered
 *
 * Subtracting only the UNLEDGERED portion avoids double-counting consumption
 * that was backfilled straight into the ledger by RecordLeaveConsumptionBulk
 * (which writes ledger rows without matching leave_applications).
 *
 * No balance row for (emp, code, fy) → available = 0 (nothing was allotted).
 */
class LeaveBalanceService
{
    /** FY end-year — 2027 = Apr-2026 .. Mar-2027. */
    public function fy(?Carbon $on = null): int
    {
        $fyStart = (int) config('hreasy.company.fy_start_month', 4);
        $on ??= now();

        return $on->month >= $fyStart ? $on->year + 1 : $on->year;
    }

    /** [start, end] Carbon dates of the FY window. */
    public function fyWindow(int $fy): array
    {
        $fyStart = (int) config('hreasy.company.fy_start_month', 4);
        $start   = Carbon::create($fy - 1, $fyStart, 1)->startOfDay();

        return [$start, $start->copy()->addYear()->subDay()->endOfDay()];
    }

    /** Days of $leaveCode still available to this employee right now. */
    public function available(int $empId, ?string $leaveCode, ?int $fy = null): float
    {
        $code = trim((string) $leaveCode);
        if ($code === '') return 0.0;

        $fy  = $fy ?: $this->fy();
        $bal = LeaveBalance::where('emp_id', $empId)
            ->where('fy', $fy)
            ->where('leave_code', $code)
            ->first();

        if (!$bal) return 0.0;

        return $this->availableFor($bal, $empId, $code, $fy);
    }

    /**
     * Every active leave type with its balance for this employee — types with
     * no balance row are still returned, with available = 0.
     *
     * @return array<int, array{leave_code:string, opening:float, availed:float, closing:float, available:float}>
     */
    public function availableAll(int $empId, ?int $fy = null): array
    {
        $fy   = $fy ?: $this->fy();
        $rows = LeaveBalance::where('emp_id', $empId)->where('fy', $fy)->get()->keyBy('leave_code');

        $codes = LeaveType::where('active_flag', true)
            ->orderBy('leave_code')
            ->pluck('leave_code')
            ->filter()
            ->values();

        // Include any balance row whose code is no longer an active leave type,
        // so the UI never silently hides leave the employee actually holds.
        $codes = $codes->merge($rows->keys())->unique()->values();

        return $codes->map(function ($code) use ($rows, $empId, $fy) {
            $bal = $rows->get($code);

            return [
                'leave_code' => (string) $code,
                'opening'    => (float) ($bal->opening_balance ?? 0),
                'availed'    => (float) ($bal->availed_ytd ?? 0),
                'closing'    => (float) ($bal->closing_balance ?? 0),
                'available'  => $bal ? $this->availableFor($bal, $empId, (string) $code, $fy) : 0.0,
            ];
        })->all();
    }

    /** closing_balance minus applications not yet reflected in availed_ytd. */
    private function availableFor(LeaveBalance $bal, int $empId, string $code, int $fy): float
    {
        [$from, $to] = $this->fyWindow($fy);

        $appliedInFy = (float) LeaveApplication::where('emp_id', $empId)
            ->where('leave_code', $code)
            ->where('active_flag', true)
            ->whereIn('approval_status', ['Pending', 'Approved'])
            ->whereBetween('from_date', [$from->toDateString(), $to->toDateString()])
            ->sum('days');

        $unledgered = max(0.0, $appliedInFy - (float) ($bal->availed_ytd ?? 0));

        return (float) ($bal->closing_balance ?? 0) - $unledgered;
    }
}
