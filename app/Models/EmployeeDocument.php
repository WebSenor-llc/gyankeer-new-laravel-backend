<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: employee_documents
 */
class EmployeeDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_documents';
    protected $primaryKey = 'doc_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'verified_date' => 'date',
        'uploaded_on' => 'date',
        'expiry_date' => 'date',
        'last_accessed_at' => 'datetime',
        'active_flag' => 'boolean',
    ];

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
