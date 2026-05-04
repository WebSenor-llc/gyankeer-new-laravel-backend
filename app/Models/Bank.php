<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: banks
 */
class Bank extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'banks';
    protected $primaryKey = 'bank_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'default_bank_flag' => 'boolean',
        'active_flag' => 'boolean',
        'created_at' => 'datetime',
    ];

    // No foreign-key relationships in this table
}
