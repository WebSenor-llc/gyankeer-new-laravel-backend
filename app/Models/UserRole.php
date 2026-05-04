<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Hreasy by WebSenor — Model for table: users_roles
 *
 * Acts as the authenticatable user for this app.
 */
class UserRole extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users_roles';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'locked_flag'      => 'boolean',
        'last_login_at'    => 'datetime',
        'created_at'       => 'datetime',
        'active_flag'      => 'boolean',
        'two_factor_enabled' => 'boolean',
        'password'         => 'hashed',
    ];

    /**
     * Use login_email as the auth username field.
     */
    public function getAuthIdentifierName()
    {
        return 'user_id';
    }

    /**
     * Convenience accessor so blade `auth()->user()->name` keeps working.
     */
    public function getNameAttribute(): string
    {
        return $this->username ?? $this->login_email ?? 'User';
    }

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
