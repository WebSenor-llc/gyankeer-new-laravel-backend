<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hreasy by WebSenor — Model for table: employee_statutory_changes
 * Effective-dated PF / EPS / ESI remove/restore history.
 */
class EmployeeStatutoryChange extends Model
{
    protected $table = 'employee_statutory_changes';

    protected $guarded = [];

    protected $casts = [
        'effective_from' => 'date',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public const SCHEMES = ['pf', 'eps', 'esi'];

    public const SCHEME_LABELS = ['pf' => 'PF', 'eps' => 'EPS (FPF)', 'esi' => 'ESI'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'changed_by', 'user_id');
    }
}
