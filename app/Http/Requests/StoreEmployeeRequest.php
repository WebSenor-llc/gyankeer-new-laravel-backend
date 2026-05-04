<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Identifiers
            'emp_id'              => 'required|string|unique:employees,emp_id|max:30',
            'third_party_code'    => 'nullable|string|max:30',

            // Type / Role
            'employee_type'       => 'required|in:ST,SB,WK',
            'role'                => 'required|string|max:50',
            'company_id'          => 'required|exists:companies,company_id',
            'salary_group_id'     => 'required|exists:salary_groups,salary_group_id',

            // Personal
            'name_prefix'         => 'required|in:Mr,Mrs,Ms,Dr,Smt,Shri,Kum',
            'first_name'          => 'required|string|max:50',
            'last_name'           => 'nullable|string|max:50',
            'gender'              => 'required|in:M,F,Other',
            'marital_status'      => 'nullable|in:Single,Married,Divorced,Widowed,Separated',
            'blood_group'         => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',

            // Family
            'relation_prefix'     => 'nullable|in:S/O,D/O,W/O,C/O',
            'relative_name'       => 'required|string|max:100',

            // Birth & joining
            'dob'                 => 'required|date|before:today',
            'date_of_joining'     => 'required|date',
            'on_probation'        => 'nullable|boolean',
            'probation_period_months' => 'nullable|integer|min:0|max:24',

            // Work
            'dept_id'             => 'required|exists:departments,dept_id',
            'designation_id'      => 'required|exists:designations,designation_id',
            'work_place'          => 'required|string|max:30',

            // Contact
            'company_mobile'      => 'nullable|regex:/^[6-9]\d{9}$/',
            'company_email'       => 'nullable|email|max:100',
            'emergency_contact_phone' => 'required|string|max:20',

            // Govt IDs (Indian)
            'aadhar_id_no'        => 'nullable|digits:12',
            'pan_no'              => 'nullable|regex:/^[A-Z]{5}\d{4}[A-Z]$/',
            'uan'                 => 'nullable|digits:12',

            // Address
            'mailing_address_line1' => 'required|string|max:255',
            'mailing_city'        => 'required|string|max:50',
            'mailing_state'       => 'required|string|max:50',
            'mailing_pincode'     => 'required|digits:6',

            // Salary
            'current_gross'       => 'required|numeric|min:0',
            'tax_regime'          => 'nullable|in:Old,New',
        ];
    }

    public function messages(): array
    {
        return [
            'pan_no.regex'   => 'PAN must be in format ABCDE1234F (5 letters · 4 digits · 1 letter).',
            'aadhar_id_no.digits' => 'Aadhaar must be exactly 12 digits.',
            'uan.digits'     => 'UAN must be exactly 12 digits.',
            'company_mobile.regex' => 'Mobile must be 10 digits starting with 6/7/8/9.',
            'mailing_pincode.digits' => 'PIN code must be 6 digits.',
        ];
    }
}
