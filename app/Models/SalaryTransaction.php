<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: salary_transactions
 */
class SalaryTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salary_transactions';
    protected $primaryKey = 'txn_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'txn_date' => 'date',
        'reversed_flag' => 'boolean',
        'posted_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(SalaryRun::class, 'run_id');
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
