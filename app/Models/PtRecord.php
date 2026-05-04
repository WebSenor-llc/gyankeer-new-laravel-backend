<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: pt_records
 */
class PtRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pt_records';
    protected $primaryKey = 'pt_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
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
}
