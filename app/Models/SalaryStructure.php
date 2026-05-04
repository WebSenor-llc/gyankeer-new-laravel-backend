<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: salary_structures
 */
class SalaryStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salary_structures';
    protected $primaryKey = 'structure_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'variable_pay_pct' => 'decimal:2',
        'approval_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function salary_group(): BelongsTo
    {
        return $this->belongsTo(SalaryGroup::class, 'salary_group_id');
    }

    public function approver_emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_emp_id');
    }
}
