<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hreasy by WebSenor — Model for table: employees
 */
class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';
    protected $primaryKey = 'emp_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'dob' => 'date',
        'on_probation' => 'boolean',
        'probation_end_date' => 'date',
        'confirmed_flag' => 'boolean',
        'epf_join_date' => 'date',
        'eps_join_date' => 'date',
        'epf_form11_submitted_on' => 'date',
        'esi_join_date' => 'date',
        'esi_form1_submitted_on' => 'date',
        'senior_citizen_flag' => 'boolean',
        'super_senior_citizen_flag' => 'boolean',
        'gratuity_form_f_filed_on' => 'date',
        'last_increment_date' => 'date',
        'last_increment_pct' => 'decimal:2',
        'secondary_share_pct' => 'decimal:2',
        'penny_drop_date' => 'date',
        'bgv_completed_date' => 'date',
        'posh_training_date' => 'date',
        'code_of_conduct_signed_on' => 'date',
        'nda_signed_on' => 'date',
        'high_potential_flag' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_login_at' => 'datetime',
        'active_flag' => 'boolean',
        // SUGAM-style salary configuration flags (see /payroll/manage-salary/{id}/config)
        'pf_applicable_flag'        => 'boolean',
        'fpf_applicable_flag'       => 'boolean',
        'esi_applicable_flag'       => 'boolean',
        'co_applicable_flag'        => 'boolean',
        'overtime_applicable_flag'  => 'boolean',
        'lwf_apply_flag'            => 'boolean',
        'ltc_entitled_flag'         => 'boolean',
        'auto_calc_flag'            => 'boolean',
        'notice_served_flag'        => 'boolean',
        'date_of_relieving'         => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function salary_group(): BelongsTo
    {
        return $this->belongsTo(SalaryGroup::class, 'salary_group_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function dept(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function secondary_bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'secondary_bank_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /** Alias used by some controllers/views. */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    /** Alias used by some controllers/views. */
    public function salaryGroup(): BelongsTo
    {
        return $this->belongsTo(SalaryGroup::class, 'salary_group_id');
    }
}
