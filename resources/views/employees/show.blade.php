@extends('layouts.app')

@section('title', $emp->full_name ?? 'Employee Profile')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">
        HR / Master Config /
        <a href="{{ route('employees.index') }}" class="hover:underline">Manage Employee</a> /
        <span class="text-slate-900 font-semibold">{{ $emp->full_name ?? ('Employee #' . $emp->emp_id) }}</span>
    </div>

    {{-- Header card --}}
    <div class="card p-5 mb-4">
        <div class="flex items-start gap-4">
            @if($emp->photo_path)
                <img src="{{ asset('storage/'.$emp->photo_path) }}" alt="{{ $emp->full_name }}"
                     class="w-16 h-16 rounded-full object-cover flex-shrink-0 border border-[var(--line)]">
            @else
                <div class="w-16 h-16 rounded-full grad-red text-white text-2xl font-bold flex items-center justify-center flex-shrink-0">
                    {{ strtoupper(substr($emp->full_name ?? 'U', 0, 1)) }}
                </div>
            @endif
            <div class="flex-1">
                <h1 class="text-xl font-bold">{{ $emp->full_name ?? 'Unnamed' }}</h1>
                <div class="text-sm text-slate-500 mt-1">
                    {{ $emp->designation->designation_name ?? '—' }}
                    @if($emp->department)
                        • <span class="text-slate-700">{{ $emp->department->dept_name }}</span>
                    @endif
                    @if($emp->employee_type)
                        • <span class="pill pill-info">{{ $emp->employee_type }}</span>
                    @endif
                    @if($emp->employment_status === 'Active')
                        <span class="pill pill-ok">Active</span>
                    @elseif($emp->employment_status === 'Exited' || $emp->employment_status === 'Resigned')
                        <span class="pill pill-bad">{{ $emp->employment_status }}</span>
                    @else
                        <span class="pill pill-warn">{{ $emp->employment_status ?? 'Unknown' }}</span>
                    @endif
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 text-sm">
                    <div><div class="text-[11px] text-slate-500 uppercase">Emp ID</div><div class="font-semibold">{{ $emp->emp_id }}</div></div>
                    <div><div class="text-[11px] text-slate-500 uppercase">3P Code</div><div class="font-semibold">{{ $emp->third_party_code ?? '—' }}</div></div>
                    <div><div class="text-[11px] text-slate-500 uppercase">Date of Joining</div><div class="font-semibold">{{ $emp->date_of_joining ? \Carbon\Carbon::parse($emp->date_of_joining)->format('d-M-Y') : '—' }}</div></div>
                    <div><div class="text-[11px] text-slate-500 uppercase">Current Gross</div><div class="font-semibold">&#8377;{{ number_format($emp->current_gross ?? 0, 0) }}</div></div>
                </div>
            </div>
            <div>
                <a href="{{ route('employees.edit', $emp->emp_id) }}" class="tb-btn primary">Edit</a>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    @php
        $tabs = [
            'profile'    => ['Profile',    route('employees.show', $emp->emp_id)],
            'education'  => ['Education',  route('employees.education', $emp->emp_id)],
            'employment' => ['Employment', route('employees.employment', $emp->emp_id)],
            'statutory'  => ['Statutory',  route('employees.statutory', $emp->emp_id)],
            'bank'       => ['Bank',       route('employees.bank', $emp->emp_id)],
            'documents'  => ['Documents',  route('employees.documents', $emp->emp_id)],
            'family'     => ['Family',     route('employees.family', $emp->emp_id)],
            'career'     => ['Career',     route('employees.career', $emp->emp_id)],
            'salary'     => ['Salary',     route('employees.salary', $emp->emp_id)],
        ];
    @endphp
    <div class="card mb-4">
        <div class="flex flex-wrap gap-1 p-2 border-b border-[var(--line)]">
            @foreach($tabs as $key => $t)
                <a href="{{ $t[1] }}"
                   class="px-3 py-1.5 rounded-md text-sm font-medium {{ $tab === $key ? 'text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                   style="{{ $tab === $key ? 'background:var(--brand)' : '' }}">
                    {{ $t[0] }}
                </a>
            @endforeach
        </div>

        <div class="p-5">
            @switch($tab)
                @case('profile')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Full Name</div><div>{{ $emp->full_name ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Father's Name</div><div>{{ $emp->fathers_name ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Mother's Name</div><div>{{ $emp->mothers_name ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">DOB</div><div>{{ $emp->dob ? \Carbon\Carbon::parse($emp->dob)->format('d-M-Y') : '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Gender</div><div>{{ $emp->gender ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Marital Status</div><div>{{ $emp->marital_status ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Blood Group</div><div>{{ $emp->blood_group ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Nationality</div><div>{{ $emp->nationality ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Personal Mobile</div><div>{{ $emp->personal_mobile ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Personal Email</div><div>{{ $emp->personal_email ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Company Email</div><div>{{ $emp->company_email ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Company Mobile</div><div>{{ $emp->company_mobile ?? '—' }}</div></div>
                    </div>
                    @break

                @case('employment')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Company</div><div>{{ $emp->company->company_name ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Department</div><div>{{ $emp->department->dept_name ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Designation</div><div>{{ $emp->designation->designation_name ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Employment Status</div><div>{{ $emp->employment_status ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Employee Type</div><div>{{ $emp->employee_type ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Date of Joining</div><div>{{ $emp->date_of_joining ? \Carbon\Carbon::parse($emp->date_of_joining)->format('d-M-Y') : '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Last Working Day</div><div>{{ $emp->date_of_relieving ? \Carbon\Carbon::parse($emp->date_of_relieving)->format('d-M-Y') : '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Probation Period (months)</div><div>{{ $emp->probation_period_months ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Confirmed</div><div>{{ $emp->confirmed_flag ? 'Yes' : 'No' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Cost Center</div><div>{{ $emp->cost_center ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Business Unit</div><div>{{ $emp->business_unit ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Shift</div><div>{{ $emp->shift->shift_name ?? '—' }}</div></div>
                    </div>
                    @break

                @case('statutory')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">PAN</div><div>{{ $emp->pan_no ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Aadhar</div><div>{{ $emp->aadhar_id_no ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">UAN</div><div>{{ $emp->uan ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">EPF Member ID</div><div>{{ $emp->epf_member_id ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">EPF Join Date</div><div>{{ $emp->epf_join_date ? \Carbon\Carbon::parse($emp->epf_join_date)->format('d-M-Y') : '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">ESI IP No</div><div>{{ $emp->esi_ip_no ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">ESI Join Date</div><div>{{ $emp->esi_join_date ? \Carbon\Carbon::parse($emp->esi_join_date)->format('d-M-Y') : '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">PT State</div><div>{{ $emp->pt_state ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">LWF State</div><div>{{ $emp->lwf_state ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Tax Regime</div><div>{{ $emp->tax_regime ?? 'New' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">VPF Amount</div><div>&#8377;{{ number_format($emp->vpf_amount ?? 0, 0) }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Passport No</div><div>{{ $emp->passport_no ?? '—' }}</div></div>
                    </div>
                    @break

                @case('bank')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Bank</div><div>{{ $emp->bank->bank_name ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Account Holder</div><div>{{ $emp->account_holder_name ?? $emp->full_name }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Account No</div><div>{{ $emp->bank_account_no ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">IFSC</div><div>{{ $emp->bank_ifsc ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">MICR</div><div>{{ $emp->bank_micr ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Branch</div><div>{{ $emp->bank_branch ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Account Type</div><div>{{ $emp->account_type ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Disbursement Mode</div><div>{{ $emp->salary_disbursement_mode ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">UPI VPA</div><div>{{ $emp->upi_vpa ?? '—' }}</div></div>
                        <div><div class="text-[11px] text-slate-500 uppercase mb-1">Penny Drop Verified</div><div>{{ $emp->penny_drop_verified ? 'Yes' : 'No' }}</div></div>
                    </div>
                    @break

                @case('salary')
                    <div class="mb-3">
                        <div class="text-[11px] text-slate-500 uppercase">Salary Group</div>
                        <div class="font-semibold">{{ $emp->salary_group->salary_group_name ?? '—' }}</div>
                    </div>
                    <table class="grid-tbl">
                        <tr><th>Basic</th><td>&#8377;{{ number_format($emp->current_basic ?? 0, 0) }}</td></tr>
                        <tr><th>HRA</th><td>&#8377;{{ number_format($emp->current_hra ?? 0, 0) }}</td></tr>
                        <tr><th>DA</th><td>&#8377;{{ number_format($emp->current_da ?? 0, 0) }}</td></tr>
                        <tr><th>Conveyance</th><td>{{ $emp->current_conv ?? '—' }}</td></tr>
                        <tr><th>Medical</th><td>{{ $emp->current_med ?? '—' }}</td></tr>
                        <tr><th>Special Allowance</th><td>&#8377;{{ number_format($emp->current_spl ?? 0, 0) }}</td></tr>
                        <tr><th>Gross</th><td><strong>&#8377;{{ number_format($emp->current_gross ?? 0, 0) }}</strong></td></tr>
                        <tr><th>CTC</th><td><strong style="color:var(--brand)">&#8377;{{ number_format($emp->current_ctc ?? 0, 0) }}</strong></td></tr>
                        <tr><th>Last Increment %</th><td>{{ $emp->last_increment_pct ?? '—' }}</td></tr>
                        <tr><th>Last Increment Date</th><td>{{ $emp->last_increment_date ? \Carbon\Carbon::parse($emp->last_increment_date)->format('d-M-Y') : '—' }}</td></tr>
                    </table>
                    @break

                @case('education')
                @case('documents')
                @case('family')
                @case('career')
                    <div class="text-center py-8 text-slate-500">
                        <div class="text-3xl mb-2">📄</div>
                        <div class="font-semibold">{{ ucfirst($tab) }} records will appear here.</div>
                        <p class="text-xs mt-1">This module connects to the <code>employee_{{ $tab }}</code> table — no records yet for this employee.</p>
                    </div>
                    @break
            @endswitch
        </div>
    </div>
</div>
@endsection
