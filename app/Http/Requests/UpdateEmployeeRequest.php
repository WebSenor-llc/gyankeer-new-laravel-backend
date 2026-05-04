<?php

namespace App\Http\Requests;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $empId = $this->route('empId');

        // Allow same emp_id when updating
        $rules['emp_id'] = "required|string|unique:employees,emp_id,{$empId},emp_id|max:30";

        // All fields become 'sometimes' for partial updates
        return collect($rules)
            ->mapWithKeys(fn($v,$k) => [$k => str_starts_with($v,'required') ? str_replace('required','sometimes|required', $v) : $v])
            ->toArray();
    }
}
