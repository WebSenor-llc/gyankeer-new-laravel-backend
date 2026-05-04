<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: letters_issued
 */
class LetterIssued extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'letters_issued';
    protected $primaryKey = 'letter_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'effective_date' => 'date',
        'dsc_signed_at' => 'datetime',
        'delivered_at' => 'datetime',
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

    public function signed_by_emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'signed_by_emp_id');
    }
}
