<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSummary extends Model
{
    use SoftDeletes;

    protected $table = 'attendance_summary';
    protected $primaryKey = 'summary_id';
    protected $guarded = [];

    protected $casts = [
        'p_count'    => 'decimal:2',
        'w_count'    => 'decimal:2',
        'cl_count'   => 'decimal:2',
        'sl_count'   => 'decimal:2',
        'pl_count'   => 'decimal:2',
        'a_count'    => 'decimal:2',
        'hd_count'   => 'decimal:2',
        'ot_hours'   => 'decimal:2',
        'total_days' => 'decimal:2',
    ];

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    /** Total leave days (counts toward paid leave bucket; no LOP). */
    public function totalLeaveDays(): float
    {
        return (float) ($this->cl_count + $this->sl_count + $this->pl_count);
    }

    /** Days that count as worked (Present + half-days). */
    public function workedDayEquivalents(): float
    {
        // P_count already includes 0.5 for half-Present; HD count counts as 0.5 worked
        return (float) $this->p_count + (float) $this->hd_count * 0.5;
    }
}
