@extends('layouts.app')
@section('title', 'Edit Employee')

@section('content')
<div class="p-4 max-w-5xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        HR / Master Config /
        <a href="{{ route('employees.index') }}" class="hover:underline">Manage Employee</a> /
        <a href="{{ route('employees.show', $emp->emp_id) }}" class="hover:underline">{{ $emp->full_name }}</a> /
        <span class="text-slate-900 font-semibold">Edit</span>
    </div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Edit — {{ $emp->full_name }}</h1>
        <a href="{{ route('employees.show', $emp->emp_id) }}" class="tb-btn">&larr; Back to profile</a>
    </div>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('employees.update', $emp->emp_id) }}" class="card p-5 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        {{-- Photo upload --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600 border-b border-[var(--line)] pb-2">Employee Photo</h2>
            <div class="flex items-center gap-4">
                @php $hasPhoto = !empty($emp->photo_path); @endphp
                <div id="photoPreview"
                     class="w-24 h-24 rounded-full grad-red text-white text-2xl font-bold flex items-center justify-center overflow-hidden border border-[var(--line)] bg-slate-100 bg-center bg-cover"
                     style="{{ $hasPhoto ? 'background-image:url('.asset('storage/'.$emp->photo_path).')' : '' }}">
                    <span id="photoInitials" style="{{ $hasPhoto ? 'display:none' : '' }}">
                        {{ strtoupper(substr($emp->first_name ?? '', 0, 1).substr($emp->last_name ?? '', 0, 1)) ?: 'U' }}
                    </span>
                </div>
                <div class="flex-1">
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                           onchange="(function(i){if(!i.files[0])return;var r=new FileReader();r.onload=function(e){var p=document.getElementById('photoPreview');p.style.backgroundImage='url('+e.target.result+')';document.getElementById('photoInitials').style.display='none';};r.readAsDataURL(i.files[0]);})(this)"
                           class="block text-sm">
                    <p class="text-[11px] text-slate-500 mt-1">JPG / PNG / WebP. Max 2 MB. Upload to replace the current photo.</p>
                    @if($hasPhoto)
                        <label class="inline-flex items-center gap-1 mt-2 text-xs text-red-700">
                            <input type="checkbox" name="remove_photo" value="1" class="rounded">
                            Remove current photo
                        </label>
                    @endif
                </div>
            </div>
        </div>

        {{-- Identity --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600">Identity</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                @php $f = function($name, $label, $value, $type='text', $extra='') {
                    echo '<div><label class="block text-xs font-semibold text-slate-600 mb-1.5">'.$label.'</label>
                        <input type="'.$type.'" name="'.$name.'" value="'.e(old($name, $value)).'" '.$extra.'
                        class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]"></div>';
                }; @endphp
                {!! $f('first_name',   'First Name',     $emp->first_name)!!}
                {!! $f('middle_name',  'Middle Name',    $emp->middle_name)!!}
                {!! $f('last_name',    'Last Name',      $emp->last_name)!!}
                {!! $f('full_name',    'Full Name',      $emp->full_name)!!}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Relation Prefix</label>
                    <select name="relation_prefix" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['','S/O','D/O','W/O','C/O'] as $p)<option value="{{ $p }}" @selected(old('relation_prefix', $emp->relation_prefix)===$p)>{{ $p }}</option>@endforeach
                    </select>
                </div>
                {!! $f('relative_name', "Father's / Husband's Name", $emp->relative_name)!!}
                {!! $f('fathers_name',  "Father's Name",            $emp->fathers_name)!!}
                {!! $f('spouse_name',   "Spouse Name",              $emp->spouse_name)!!}
                {!! $f('mothers_name',  "Mother's Name",            $emp->mothers_name)!!}
                {!! $f('dob',          'Date of Birth',  $emp->dob ? \Carbon\Carbon::parse($emp->dob)->format('Y-m-d') : '', 'date')!!}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Gender</label>
                    <select name="gender" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">—</option>
                        @foreach(['Male','Female','Other'] as $g)<option value="{{ $g }}" @selected($emp->gender === $g)>{{ $g }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Marital Status</label>
                    <select name="marital_status" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">—</option>
                        @foreach(['Single','Married','Divorced','Widowed'] as $g)<option value="{{ $g }}" @selected($emp->marital_status === $g)>{{ $g }}</option>@endforeach
                    </select>
                </div>
                {!! $f('blood_group', 'Blood Group', $emp->blood_group)!!}
                {!! $f('nationality', 'Nationality', $emp->nationality)!!}
                {!! $f('personal_mobile','Personal Mobile', $emp->personal_mobile)!!}
                {!! $f('personal_email', 'Personal Email',  $emp->personal_email,  'email')!!}
                {!! $f('company_email',  'Company Email',   $emp->company_email,  'email')!!}
                {!! $f('company_mobile', 'Company Mobile',  $emp->company_mobile)!!}
            </div>
        </div>

        {{-- Employment --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600">Employment</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Department</label>
                    <select name="dept_id" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">—</option>
                        @foreach($departments as $d)<option value="{{ $d->dept_id }}" @selected($emp->dept_id == $d->dept_id)>{{ $d->dept_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Designation</label>
                    <select name="designation_id" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">—</option>
                        @foreach($designations as $d)<option value="{{ $d->designation_id }}" @selected($emp->designation_id == $d->designation_id)>{{ $d->designation_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Salary Group</label>
                    <select name="salary_group_id" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">—</option>
                        @foreach($salaryGroups as $sg)<option value="{{ $sg->salary_group_id }}" @selected($emp->salary_group_id == $sg->salary_group_id)>{{ $sg->salary_group_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Employment Status</label>
                    <select name="employment_status" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['Active','Notice','Exited','Resigned','Suspended'] as $s)<option value="{{ $s }}" @selected($emp->employment_status === $s)>{{ $s }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Employee Type</label>
                    <select name="employee_type" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['Staff','Sub-Staff','Worker','Contract','Trainee'] as $t)<option value="{{ $t }}" @selected($emp->employee_type === $t)>{{ $t }}</option>@endforeach
                    </select>
                </div>
                {!! $f('date_of_joining', 'Date of Joining',  $emp->date_of_joining ? \Carbon\Carbon::parse($emp->date_of_joining)->format('Y-m-d') : '', 'date')!!}
                {!! $f('cost_center',     'Cost Center',      $emp->cost_center)!!}
                {!! $f('business_unit',   'Business Unit',    $emp->business_unit)!!}
            </div>
        </div>

        {{-- Salary --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600">Salary</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                {!! $f('current_basic',  'Basic',     $emp->current_basic,  'number', 'step="0.01"')!!}
                {!! $f('current_hra',    'HRA',       $emp->current_hra,    'number', 'step="0.01"')!!}
                {!! $f('current_da',     'DA',        $emp->current_da,     'number', 'step="0.01"')!!}
                {!! $f('current_conv',   'Conveyance',$emp->current_conv)!!}
                {!! $f('current_med',    'Medical',   $emp->current_med)!!}
                {!! $f('education_allow','Education', $emp->education_allow,'number', 'step="0.01"')!!}
                {!! $f('current_spl',    'Special',   $emp->current_spl,    'number', 'step="0.01"')!!}
                {!! $f('current_gross',  'Gross',     $emp->current_gross,  'number', 'step="0.01"')!!}
                {!! $f('current_ctc',    'CTC',       $emp->current_ctc,    'number', 'step="0.01"')!!}
            </div>
        </div>

        {{-- Bank --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600">Bank</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bank</label>
                    <select name="bank_id" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">—</option>
                        @foreach($banks as $b)<option value="{{ $b->bank_id }}" @selected($emp->bank_id == $b->bank_id)>{{ $b->bank_name }}</option>@endforeach
                    </select>
                </div>
                {!! $f('bank_account_no', 'Account No.', $emp->bank_account_no)!!}
                {!! $f('bank_ifsc',       'IFSC',        $emp->bank_ifsc)!!}
            </div>
        </div>

        {{-- Statutory --}}
        <div>
            <h2 class="font-semibold mb-3 text-sm uppercase tracking-wide text-slate-600">Statutory</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                {!! $f('pan_no',        'PAN',          $emp->pan_no)!!}
                {!! $f('aadhar_id_no',  'Aadhar',       $emp->aadhar_id_no)!!}
                {!! $f('uan',           'UAN',          $emp->uan)!!}
                {!! $f('epf_member_id', 'EPF Member ID',$emp->epf_member_id)!!}
                {!! $f('esi_ip_no',     'ESI IP No',    $emp->esi_ip_no)!!}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tax Regime</label>
                    <select name="tax_regime" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        @foreach(['New','Old'] as $r)<option value="{{ $r }}" @selected($emp->tax_regime === $r)>{{ $r }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-[var(--line)]">
            <a href="{{ route('employees.show', $emp->emp_id) }}" class="tb-btn">Cancel</a>
            <button type="submit" class="tb-btn primary">Save Changes</button>
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
