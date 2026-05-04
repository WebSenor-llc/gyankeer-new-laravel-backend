<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: payslips
 */
class Payslip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payslips';
    protected $primaryKey = 'payslip_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'disbursement_date' => 'date',
        'generated_at' => 'datetime',
        'signed_at' => 'datetime',
        'emailed_at' => 'datetime',
        'whatsapp_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(SalaryRun::class, 'run_id');
    }

    public function emp(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}
