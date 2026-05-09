<?php
/**
 * Hreasy by WebSenor — Statutory configuration for Indian payroll
 *
 * All rates and caps centralised here. Override via .env per environment.
 * Last reviewed: FY 2026-27 (Finance Act 2024)
 */

return [
    'company' => [
        'name' => env('APP_NAME', 'Hreasy by WebSenor'),
        'fy_start_month' => env('HREASY_FY_START_MONTH', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payroll wage rules
    |--------------------------------------------------------------------------
    | worker_divisor — for contract workers, daily salary = monthly ÷ this.
    |                  Default 26 (excludes 4 Sundays in a typical month).
    */
    'payroll' => [
        'worker_divisor' => env('HREASY_WORKER_DIVISOR', 26),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provident Fund (EPF Act 1952)
    |--------------------------------------------------------------------------
    */
    'pf' => [
        'employee_rate'      => env('HREASY_PF_RATE_EMPLOYEE', 12),    // %
        'employer_rate'      => env('HREASY_PF_RATE_EMPLOYER', 3.67),  // %
        'eps_rate'           => env('HREASY_EPS_RATE', 8.33),          // %
        'edli_rate'          => env('HREASY_EDLI_RATE', 0.5),          // %
        'admin_rate'         => env('HREASY_PF_ADMIN_RATE', 0.5),      // %
        'wage_cap'           => env('HREASY_PF_WAGE_CAP', 15000),
        'eps_wage_cap'       => 15000,
        'edli_wage_cap'      => 15000,
        'edli_max_benefit'   => 700000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Employee State Insurance (ESI Act 1948)
    |--------------------------------------------------------------------------
    */
    'esi' => [
        'employee_rate' => env('HREASY_ESI_RATE_EMPLOYEE', 0.75), // %
        'employer_rate' => env('HREASY_ESI_RATE_EMPLOYER', 3.25), // %
        'wage_cap'      => env('HREASY_ESI_WAGE_CAP', 21000),
        'wage_cap_pwd'  => 25000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Profession Tax (state-wise slabs)
    |--------------------------------------------------------------------------
    */
    'pt' => [
        'MH' => [ // Maharashtra
            ['min' => 0,     'max' => 7500,  'amount' => 0],
            ['min' => 7501,  'max' => 10000, 'amount' => 175],
            ['min' => 10001, 'max' => 999999,'amount' => 200, 'feb_amount' => 300],
        ],
        'KA' => [ // Karnataka
            ['min' => 0,     'max' => 15000, 'amount' => 0],
            ['min' => 15001, 'max' => 999999,'amount' => 200],
        ],
        'WB' => [ // West Bengal
            ['min' => 0,     'max' => 10000, 'amount' => 0],
            ['min' => 10001, 'max' => 15000, 'amount' => 110],
            ['min' => 15001, 'max' => 25000, 'amount' => 130],
            ['min' => 25001, 'max' => 40000, 'amount' => 150],
            ['min' => 40001, 'max' => 999999,'amount' => 200],
        ],
        'TN' => [ // Tamil Nadu (half-yearly)
            ['min' => 0,      'max' => 21000,  'amount' => 0],
            ['min' => 21001,  'max' => 30000,  'amount' => 22.5],
            ['min' => 30001,  'max' => 45000,  'amount' => 52.5],
            ['min' => 45001,  'max' => 60000,  'amount' => 115],
            ['min' => 60001,  'max' => 75000,  'amount' => 171.5],
            ['min' => 75001,  'max' => 999999, 'amount' => 208.5],
        ],
        'TG' => [ // Telangana
            ['min' => 0,     'max' => 15000, 'amount' => 0],
            ['min' => 15001, 'max' => 20000, 'amount' => 150],
            ['min' => 20001, 'max' => 999999,'amount' => 200],
        ],
        'GJ' => [ // Gujarat
            ['min' => 0,     'max' => 12000, 'amount' => 0],
            ['min' => 12001, 'max' => 999999,'amount' => 200],
        ],
        'KL' => [ // Kerala (half-yearly)
            ['min' => 0,      'max' => 11999,  'amount' => 0],
            ['min' => 12000,  'max' => 17999,  'amount' => 120],
            ['min' => 18000,  'max' => 29999,  'amount' => 180],
            ['min' => 30000,  'max' => 44999,  'amount' => 300],
            ['min' => 45000,  'max' => 59999,  'amount' => 450],
            ['min' => 60000,  'max' => 74999,  'amount' => 600],
            ['min' => 75000,  'max' => 99999,  'amount' => 750],
            ['min' => 100000, 'max' => 124999, 'amount' => 1000],
            ['min' => 125000, 'max' => 999999, 'amount' => 1250],
        ],
        // states without PT
        'DL' => [], 'UP' => [], 'HR' => [], 'RJ' => [], 'CH' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Labour Welfare Fund (state-wise)
    |--------------------------------------------------------------------------
    */
    'lwf' => [
        'MH' => ['employee' => 25, 'employer' => 75, 'frequency' => 'half_yearly', 'months' => [6,12]],
        'KA' => ['employee' => 20, 'employer' => 40, 'frequency' => 'annual', 'months' => [12]],
        'TN' => ['employee' => 10, 'employer' => 20, 'frequency' => 'annual', 'months' => [3]],
        'WB' => ['employee' => 3,  'employer' => 15, 'frequency' => 'half_yearly', 'months' => [6,12]],
        'GJ' => ['employee' => 6,  'employer' => 12, 'frequency' => 'half_yearly', 'months' => [6,12]],
        'PB' => ['employee' => 5,  'employer' => 20, 'frequency' => 'monthly'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Income Tax — TDS slabs §192 (FY 2025-26)
    |--------------------------------------------------------------------------
    */
    'tds' => [
        'standard_deduction_new' => env('HREASY_STD_DEDUCTION_NEW', 75000),
        'standard_deduction_old' => env('HREASY_STD_DEDUCTION_OLD', 50000),
        'cess_rate'              => env('HREASY_CESS_RATE', 4),
        'rebate_87a_new_limit'   => 1200000,
        'rebate_87a_new_amount'  => 60000,
        'rebate_87a_old_limit'   => 500000,
        'rebate_87a_old_amount'  => 12500,
        'surcharge_thresholds'   => [
            ['min' => 5000000,  'max' => 10000000,  'rate' => 10],
            ['min' => 10000000, 'max' => 20000000,  'rate' => 15],
            ['min' => 20000000, 'max' => 50000000,  'rate' => 25],
            ['min' => 50000000, 'max' => PHP_INT_MAX, 'rate' => 37],
        ],
        'slabs_new_fy_2025_26' => [
            ['min' => 0,        'max' => 400000,      'rate' => 0],
            ['min' => 400001,   'max' => 800000,      'rate' => 5],
            ['min' => 800001,   'max' => 1200000,     'rate' => 10],
            ['min' => 1200001,  'max' => 1600000,     'rate' => 15],
            ['min' => 1600001,  'max' => 2000000,     'rate' => 20],
            ['min' => 2000001,  'max' => 2400000,     'rate' => 25],
            ['min' => 2400001,  'max' => PHP_INT_MAX, 'rate' => 30],
        ],
        'slabs_old_fy_2025_26' => [
            ['min' => 0,       'max' => 250000,      'rate' => 0],
            ['min' => 250001,  'max' => 500000,      'rate' => 5],
            ['min' => 500001,  'max' => 1000000,     'rate' => 20],
            ['min' => 1000001, 'max' => PHP_INT_MAX, 'rate' => 30],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bonus Act 1965
    |--------------------------------------------------------------------------
    */
    'bonus' => [
        'min_rate'    => env('HREASY_BONUS_MIN_PCT', 8.33),
        'max_rate'    => env('HREASY_BONUS_MAX_PCT', 20),
        'wage_cap'    => env('HREASY_BONUS_WAGE_CAP', 7000),
        'salary_cap'  => env('HREASY_BONUS_SALARY_CAP', 21000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gratuity (Payment of Gratuity Act 1972)
    |--------------------------------------------------------------------------
    */
    'gratuity' => [
        'rate'                 => env('HREASY_GRATUITY_RATE', 4.81),     // % of (Basic+DA)
        'min_years_eligibility'=> 5,
        'days_per_month'       => 26,
        'days_factor'          => 15,
        'tax_free_cap'         => env('HREASY_GRATUITY_TAX_FREE_CAP', 2000000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Calendar (auto-tasks)
    |--------------------------------------------------------------------------
    */
    'compliance' => [
        'tds_due_day_of_month'   => 7,
        'pf_due_day_of_month'    => 15,
        'esi_due_day_of_month'   => 15,
        'pt_due_day_mh'          => 21,
        'form24q_due_dates'      => ['Q1'=>'31-Jul','Q2'=>'31-Oct','Q3'=>'31-Jan','Q4'=>'31-May'],
        'form16_due_date'        => '15-Jun',
        'posh_annual_report_due' => '31-Jan',
    ],

    /*
    |--------------------------------------------------------------------------
    | DPDP Act 2023 — Data Privacy
    |--------------------------------------------------------------------------
    */
    'dpdp' => [
        'data_retention_active_years'   => 7,    // IT Act
        'data_retention_post_exit_years'=> 8,    // PoG / EPF audits
        'pii_encryption'                => 'AES-256',
        'aadhaar_masking_enabled'       => true,
        'consent_required'              => true,
    ],
];
