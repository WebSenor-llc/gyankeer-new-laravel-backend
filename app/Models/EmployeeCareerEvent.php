<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: employee_career_events
 */
class EmployeeCareerEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_career_events';
    protected $primaryKey = 'event_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'event_date' => 'date',
        'hike_percent' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function approver_emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_emp_id');
    }
}
