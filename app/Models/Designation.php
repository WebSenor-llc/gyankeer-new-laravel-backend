<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: designations
 */
class Designation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'designations';
    protected $primaryKey = 'designation_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'people_manager_flag' => 'boolean',
        'apprentice_flag' => 'boolean',
        'contract_flag' => 'boolean',
        'active_flag' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function dept(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }
}
