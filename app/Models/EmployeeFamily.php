<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: employee_family
 */
class EmployeeFamily extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_family';
    protected $primaryKey = 'family_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'dob' => 'date',
        'dependent_flag' => 'boolean',
        'minor_flag' => 'boolean',
        'active_flag' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
