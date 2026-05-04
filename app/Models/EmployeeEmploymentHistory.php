<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: employee_employment_history
 */
class EmployeeEmploymentHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_employment_history';
    protected $primaryKey = 'emp_hist_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'notice_served_flag' => 'boolean',
        'verified_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
