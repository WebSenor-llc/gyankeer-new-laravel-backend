<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: attendance_daily
 */
class AttendanceDaily extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendance_daily';
    protected $primaryKey = 'attn_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'attn_date' => 'date',
        'late_flag' => 'boolean',
        'early_out_flag' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function approver_emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_emp_id');
    }
}
