<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: loans_advances
 */
class LoanAdvance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'loans_advances';
    protected $primaryKey = 'loan_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'interest_rate_pct' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'sanction_date' => 'date',
        'last_emi_paid_date' => 'date',
        'active_flag' => 'boolean',
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

    public function approver_emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_emp_id');
    }
}
