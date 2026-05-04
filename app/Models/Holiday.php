<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: holidays
 */
class Holiday extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'holidays';
    protected $primaryKey = 'holiday_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'holiday_date' => 'date',
        'optional_flag' => 'boolean',
        'active_flag' => 'boolean',
        'created_at' => 'datetime',
    ];

    // No foreign-key relationships in this table
}
