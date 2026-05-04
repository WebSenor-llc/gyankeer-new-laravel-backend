<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualDeduction extends Model
{
    use SoftDeletes;

    protected $table = 'manual_deductions';
    protected $primaryKey = 'manual_ded_id';
    protected $guarded = [];

    protected $casts = [
        'advance_deduction'  => 'decimal:2',
        'loan_deduction'     => 'decimal:2',
        'ag_donation'        => 'decimal:2',
        'maintenance_charge' => 'decimal:2',
        'mobile_deduction'   => 'decimal:2',
        'canteen_deduction'  => 'decimal:2',
        'tds_deduction'      => 'decimal:2',
        'vpf_deduction'      => 'decimal:2',
        'incentive_hours'    => 'decimal:2',
        'misc_deduction'     => 'decimal:2',
        'rent_meridian'      => 'decimal:2',
        'tds_override_flag'  => 'boolean',
    ];

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id', 'payslip_id');
    }

    /** Sum of all "post" deductions (the misc-style ones that go into payslip.post_deduction). */
    public function postDeductionTotal(): float
    {
        return (float) ($this->ag_donation + $this->maintenance_charge + $this->mobile_deduction
            + $this->canteen_deduction + $this->misc_deduction + $this->rent_meridian);
    }
}
