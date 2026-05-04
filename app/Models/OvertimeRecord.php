<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRecord extends Model
{
    use SoftDeletes;

    protected $table = 'overtime_records';
    protected $primaryKey = 'ot_id';
    protected $guarded = [];

    protected $casts = [
        'ot_rate'    => 'decimal:2',
        'hourly_rate'=> 'decimal:2',
        'ot_hours'   => 'decimal:2',
        'ot_amount'  => 'decimal:2',
        'ot_esi'     => 'decimal:2',
        'ot_payable' => 'decimal:2',
    ];

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    /**
     * Standard hourly wage = Gross Salary ÷ 30 calendar-days ÷ 8 hours.
     * (Earlier this used 26 working-days; HR switched to 30-day basis.)
     */
    public static function hourlyWage(Employee $emp): float
    {
        $monthly = (float) ($emp->current_gross ?? 0);
        if ($monthly <= 0) {
            // Fallback: sum of components when current_gross hasn't been computed yet
            $monthly = (float) ($emp->current_basic ?? 0)
                     + (float) ($emp->current_da ?? 0)
                     + (float) ($emp->current_hra ?? 0)
                     + (float) ($emp->current_conv ?? 0)
                     + (float) ($emp->current_med ?? 0)
                     + (float) ($emp->current_spl ?? 0);
        }
        if ($monthly <= 0) return 0.0;
        return round($monthly / 30 / 8, 4);
    }

    /**
     * Compute the SUGAM-style breakdown of an OT entry.
     *
     *   hourly_rate   = Gross/30/8  × multiplier   (₹ per OT hour)
     *   ot_amount     = hourly_rate × ot_hours
     *   ot_esi        = 0  (per HR policy: ESI is NOT deducted on OT)
     *   ot_payable    = ot_amount   (no deductions)
     *
     * Matches the columns shown in SUGAM HR's "Incentive (Manual)" listing,
     * but with the ESI column always 0 (HR's instruction).
     */
    public static function computeBreakdown(Employee $emp, float $multiplier, float $hours): array
    {
        $hourlyBase = self::hourlyWage($emp);
        $hourlyRate = round($hourlyBase * $multiplier, 4);
        $amount     = round($hourlyRate * $hours, 2);

        return [
            'hourly_rate' => $hourlyRate,
            'ot_amount'   => $amount,
            'ot_esi'      => 0.0,    // ESI is NOT deducted on OT
            'ot_payable'  => $amount, // OT is paid in full
        ];
    }

    /** Backward-compatible: returns only the OT amount (used by older callers). */
    public static function computeAmount(Employee $emp, float $rate, float $hours): float
    {
        return self::computeBreakdown($emp, $rate, $hours)['ot_amount'];
    }
}
