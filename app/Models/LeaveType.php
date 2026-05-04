<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: leave_types
 */
class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'leave_types';
    protected $primaryKey = 'leave_type_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'active_flag' => 'boolean',
        'created_at' => 'datetime',
    ];

    // No foreign-key relationships in this table
}
