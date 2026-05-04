<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: salary_runs
 */
class SalaryRun extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salary_runs';
    protected $primaryKey = 'run_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'run_started_at' => 'datetime',
        'calc_completed_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'finance_approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'paid_at' => 'datetime',
        'bank_file_generated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
