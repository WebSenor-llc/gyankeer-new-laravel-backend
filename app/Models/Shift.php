<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: shifts
 */
class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shifts';
    protected $primaryKey = 'shift_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'night_shift_flag' => 'boolean',
        'active_flag' => 'boolean',
        'created_at' => 'datetime',
    ];

    // No foreign-key relationships in this table
}
