@extends('layouts.app')
@section('title', 'Add New Employee')

@section('content')
<div class="p-4 max-w-6xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        HR / Master Config /
        <a href="{{ route('employees.index') }}" class="hover:underline">Manage Employee</a> /
        <span class="text-slate-900 font-semibold">Add</span>
    </div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Add New Employee</h1>
        <a href="{{ route('employees.index') }}" class="tb-btn">&larr; Back to list</a>
    </div>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('employees.store') }}" class="card p-5 space-y-6" enctype="multipart/form-data">
        @csrf

        {{-- Photo upload --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600 border-b border-[var(--line)] pb-2">Employee Photo</h2>
            <div class="flex items-center gap-4">
                <div id="photoPreview"
                     class="w-24 h-24 rounded-full grad-red text-white text-2xl font-bold flex items-center justify-center overflow-hidden border border-[var(--line)] bg-slate-100 bg-center bg-cover">
                    <span id="photoInitials">+</span>
                </div>
                <div class="flex-1">
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                           onchange="(function(i){if(!i.files[0])return;var r=new FileReader();r.onload=function(e){var p=document.getElementById('photoPreview');p.style.backgroundImage='url('+e.target.result+')';document.getElementById('photoInitials').style.display='none';};r.readAsDataURL(i.files[0]);})(this)"
                           class="block text-sm">
                    <p class="text-[11px] text-slate-500 mt-1">JPG / PNG / WebP. Max 2 MB. Square images look best.</p>
                </div>
            </div>
        </div>

        @php
            $f = function($name, $label, $value='', $type='text', $extra='') {
                echo '<div><label class="block text-xs font-semibold text-slate-600 mb-1.5">'.$label.'</label>
                    <input type="'.$type.'" name="'.$name.'" value="'.e(old($name, $value)).'" '.$extra.'
                    class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]"></div>';
            };
        @endphp

        {{-- Profile --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600 border-b border-[var(--line)] pb-2">Profile</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Employee Type *</label>
                    <select name="employee_type" required class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">— Select —</option>
                        @foreach(['Staff','Sub-Staff','Worker','Contract','Trainee'] as $t)
                            <option value="{{ $t }}" @selected(old('employee_type')===$t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                {!! $f('role', 'Role') !!}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Name Prefix</label>
                    <select name="name_prefix" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['','Mr.','Mrs.','Ms.','Dr.','Smt.','Shri'] as $p)<option value="{{ $p }}" @selected(old('name_prefix')===$p)>{{ $p }}</option>@endforeach
                    </select>
                </div>
                {!! $f('first_name', 'First Name *', '', 'text', 'required') !!}
                {!! $f('middle_name', 'Middle Name') !!}
                {!! $f('last_name', 'Last Name') !!}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Gender</label>
                    <select name="gender" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">—</option>
                        @foreach(['Male','Female','Other'] as $g)<option value="{{ $g }}" @selected(old('gender')===$g)>{{ $g }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Relation Prefix</label>
                    <select name="relation_prefix" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['','S/O','D/O','W/O','C/O'] as $p)<option value="{{ $p }}" @selected(old('relation_prefix')===$p)>{{ $p }}</option>@endforeach
                    </select>
                </div>
                {!! $f('relative_name', "Father's / Husband's Name") !!}
                {!! $f('mothers_name', "Mother's Name") !!}
                {!! $f('dob', 'Date of Birth', '', 'date') !!}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Marital Status</label>
                    <select name="marital_status" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">—</option>
                        @foreach(['Single','Married','Divorced','Widowed'] as $m)<option value="{{ $m }}" @selected(old('marital_status')===$m)>{{ $m }}</option>@endforeach
                    </select>
                </div>
                {!! $f('blood_group', 'Blood Group') !!}
                {!! $f('nationality', 'Nationality', 'Indian') !!}
                {!! $f('personal_mobile', 'Personal Mobile') !!}
                {!! $f('personal_email', 'Personal Email', '', 'email') !!}
                {!! $f('company_email', 'Company Email', '', 'email') !!}
                {!! $f('company_mobile', 'Company Mobile') !!}
                {!! $f('third_party_code', 'Third Party Code (Old Emp ID)') !!}
            </div>
        </div>

        {{-- Employment --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600 border-b border-[var(--line)] pb-2">Employment</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Company *</label>
                    <select name="company_id" required class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach($companies as $c)
                            <option value="{{ $c->company_id }}" @selected(old('company_id', $activeCompanyId) == $c->company_id)>{{ $c->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Department</label>
                    <select name="dept_id" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">— Select —</option>
                        @foreach($departments as $d)<option value="{{ $d->dept_id }}" @selected(old('dept_id') == $d->dept_id)>{{ $d->dept_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Designation</label>
                    <select name="designation_id" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">— Select —</option>
                        @foreach($designations as $d)<option value="{{ $d->designation_id }}" @selected(old('designation_id') == $d->designation_id)>{{ $d->designation_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Salary Group</label>
                    <select name="salary_group_id" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">— Select —</option>
                        @foreach($salaryGroups as $sg)<option value="{{ $sg->salary_group_id }}" @selected(old('salary_group_id') == $sg->salary_group_id)>{{ $sg->salary_group_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Employment Status</label>
                    <select name="employment_status" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['Active','Notice','Exited','Resigned','Suspended'] as $s)<option value="{{ $s }}" @selected(old('employment_status', 'Active')===$s)>{{ $s }}</option>@endforeach
                    </select>
                </div>
                {!! $f('grade_category', 'Category / Grade') !!}
                {!! $f('date_of_joining', 'Date of Joining', date('Y-m-d'), 'date') !!}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">On Probation</label>
                    <label class="flex items-center gap-2 mt-2">
                        <input type="checkbox" name="on_probation" value="1" @if(old('on_probation')) checked @endif>
                        <span class="text-sm">Yes</span>
                    </label>
                </div>
                {!! $f('probation_period_months', 'Probation Period (Months)', '', 'number') !!}
                {!! $f('cost_center', 'Cost Center') !!}
                {!! $f('business_unit', 'Business Unit') !!}
                {!! $f('work_place', 'Work Place') !!}
            </div>
        </div>

        {{-- Salary --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600 border-b border-[var(--line)] pb-2">Salary</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                {!! $f('current_basic',  'Basic',     '', 'number', 'step="0.01"') !!}
                {!! $f('current_hra',    'HRA',       '', 'number', 'step="0.01"') !!}
                {!! $f('current_da',     'DA',        '', 'number', 'step="0.01"') !!}
                {!! $f('current_conv',   'Conveyance','', 'number', 'step="0.01"') !!}
                {!! $f('current_med',    'Medical',   '', 'number', 'step="0.01"') !!}
                {!! $f('education_allow','Education', '', 'number', 'step="0.01"') !!}
                {!! $f('current_spl',    'Special',   '', 'number', 'step="0.01"') !!}
                {!! $f('current_gross',  'Gross',     '', 'number', 'step="0.01"') !!}
                {!! $f('current_ctc',    'CTC',       '', 'number', 'step="0.01"') !!}
            </div>
        </div>

        {{-- Bank --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600 border-b border-[var(--line)] pb-2">Bank</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bank</label>
                    <select name="bank_id" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">— Select —</option>
                        @foreach($banks as $b)<option value="{{ $b->bank_id }}" @selected(old('bank_id') == $b->bank_id)>{{ $b->bank_name }}</option>@endforeach
                    </select>
                </div>
                {!! $f('bank_account_no', 'Account No.') !!}
                {!! $f('bank_ifsc',       'IFSC') !!}
                {!! $f('account_holder_name', 'Account Holder Name') !!}
            </div>
        </div>

        {{-- Statutory --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600 border-b border-[var(--line)] pb-2">Statutory IDs</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                {!! $f('pan_no',        'PAN', '', 'text', 'maxlength="10"') !!}
                {!! $f('aadhar_id_no',  'Aadhar', '', 'text', 'maxlength="12"') !!}
                {!! $f('aadhar_enrollment_no', 'Aadhar Enrollment') !!}
                {!! $f('uan',           'UAN') !!}
                {!! $f('epf_member_id', 'EPF Member ID') !!}
                {!! $f('esi_ip_no',     'ESI IP No') !!}
                {!! $f('voter_id_no',   'Voter ID') !!}
                {!! $f('driving_license_no', 'Driving Licence No.') !!}
                {!! $f('reference_letter_no', 'Reference Letter No.') !!}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tax Regime</label>
                    <select name="tax_regime" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['New','Old'] as $r)<option value="{{ $r }}" @selected(old('tax_regime', 'New')===$r)>{{ $r }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">PT State</label>
                    <select name="pt_state" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['RJ','MH','KA','TN','TG','GJ','KL','WB','UP','HR','DL','CH'] as $s)<option value="{{ $s }}" @selected(old('pt_state','RJ')===$s)>{{ $s }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">LWF State</label>
                    <select name="lwf_state" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['RJ','MH','KA','TN','GJ','WB','PB'] as $s)<option value="{{ $s }}" @selected(old('lwf_state','RJ')===$s)>{{ $s }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600 border-b border-[var(--line)] pb-2">Address</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Mailing Address — Line 1</label>
                    <input type="text" name="mailing_address_line1" value="{{ old('mailing_address_line1') }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Mailing Address — Line 2</label>
                    <input type="text" name="mailing_address_line2" value="{{ old('mailing_address_line2') }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                </div>
                {!! $f('mailing_city', 'City') !!}
                {!! $f('mailing_state', 'State') !!}
                {!! $f('mailing_pincode', 'PIN Code') !!}
                {!! $f('mailing_country', 'Country', 'India') !!}
            </div>
            <div class="mt-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="same_as_mailing" value="1" @if(old('same_as_mailing')) checked @endif>
                    <span>Permanent address same as mailing</span>
                </label>
            </div>
        </div>

        {{-- Emergency contact --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600 border-b border-[var(--line)] pb-2">Emergency Contact</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                {!! $f('emergency_contact_name', 'Contact Name') !!}
                {!! $f('emergency_contact_relation', 'Relation') !!}
                {!! $f('emergency_contact_phone', 'Phone') !!}
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-[var(--line)]">
            <a href="{{ route('employees.index') }}" class="tb-btn">Cancel</a>
            <button type="submit" class="tb-btn primary">Save Employee</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const gross = document.querySelector('input[name="current_gross"]');
    if (!gross) return;
    const form = gross.form || gross.closest('form');
    if (!form) return;
    const parts = ['current_basic','current_hra','current_da','current_conv','current_med','education_allow','current_spl'];
    const recalc = () => {
        let sum = 0;
        parts.forEach(n => {
            const el = form.querySelector('[name="'+n+'"]');
            const v = parseFloat(el && el.value);
            if (!isNaN(v)) sum += v;
        });
        gross.value = sum ? sum.toFixed(2) : '';
    };
    parts.forEach(n => {
        const el = form.querySelector('[name="'+n+'"]');
        if (el) el.addEventListener('input', recalc);
    });
});
</script>
@endsection
