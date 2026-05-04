<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: salary_components
 */
class SalaryComponent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salary_components';
    protected $primaryKey = 'component_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'is_taxable' => 'boolean',
        'statutory_flag' => 'boolean',
        'created_at' => 'datetime',
    ];

    // No foreign-key relationships in this table
}
