@extends('layouts.app')
@section('title', 'Salary Generation')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Payroll / <span class="text-slate-900 font-semibold">Salary Generation</span></div>

    <div class="card mb-3">
        <div class="px-4 py-2 border-b border-[var(--line)] bg-slate-50 rounded-t-lg flex items-center gap-2">
            <span class="text-lg">📋</span>
            <h1 class="text-lg font-bold">Salary Generation</h1>
        </div>

        @if(session('status'))
            <div class="mx-4 mt-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        {{-- Top picker form (Get List uses GET to preview, Generate Salary submits POST) --}}
        <form method="GET" action="{{ route('payroll.generate') }}" id="pickerForm" class="p-4 grid grid-cols-12 gap-3 items-end">
            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Company :</label>
                <select name="company_id" required onchange="document.getElementById('pickerForm').submit()" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                    <option value="">— Select Company —</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->company_id }}" @selected($companyId == $c->company_id)>{{ $c->company_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Salary Group <span class="text-slate-400 font-normal">(optional)</span> :</label>
                <select name="salary_group_id" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                    <option value="">— All Groups —</option>
                    @foreach($salaryGroups as $g)
                        <option value="{{ $g->salary_group_id }}" @selected($salaryGroupId == $g->salary_group_id)>{{ $g->salary_group_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Month :</label>
                <div class="flex gap-1">
                    <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                        @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                            <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:80px">
                </div>
            </div>

            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">&nbsp;</label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="use_payroll_cycle" value="1" @checked(old('use_payroll_cycle'))>
                    <span>Use Payroll Cycle</span>
                </label>
            </div>

            <input type="hidden" name="get_list" value="1">

            <div class="col-span-12 flex gap-2 mt-1 flex-wrap">
                <button type="submit" class="tb-btn primary" style="background:#DC2626;border-color:#B91C1C;color:#fff">Get List</button>
                <button type="button" onclick="document.getElementById('genForm').submit()" class="tb-btn primary"
                        style="background:#16A34A;border-color:#15803D;color:#fff"
                        @disabled(!$previewLoaded || !$salaryGroupId)
                        title="{{ $salaryGroupId ? 'Generate salary for the picked group' : 'Pick a specific salary group first, or use Generate ALL Groups' }}">
                    Generate Salary (selected group)
                </button>
                <button type="button" onclick="generateAllGroups()" class="tb-btn primary"
                        style="background:#7C3AED;border-color:#6D28D9;color:#fff"
                        title="Skip group selection — run payroll for every salary group of this company × month in one click.">
                    ⚡ Generate ALL Groups
                </button>
                @if($previewLoaded && $employees->isNotEmpty())
                    <button type="button" onclick="exportSelected('csv')"
                            class="tb-btn primary" style="background:#16A34A;border-color:#15803D">⬇ Export CSV</button>
                    <button type="button" onclick="exportSelected('xls')"
                            class="tb-btn primary" style="background:#0EA5E9;border-color:#0284C7">⬇ Export Excel</button>
                    <button type="button" onclick="exportSelected('pdf')"
                            class="tb-btn primary" style="background:#DC2626;border-color:#B91C1C">⬇ Export PDF</button>
                    @if($existingPayslipEmps->isNotEmpty())
                        <button type="button" onclick="document.getElementById('deleteModal').style.display='flex'"
                                class="tb-btn primary" style="background:#7C2D12;border-color:#7C2D12;color:#fff">🗑️ Delete Payroll</button>
                    @endif
                    <a href="{{ route('payroll.runs.index') }}" class="tb-btn">View Runs</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Employee list (preview after Get List) --}}
    @if($previewLoaded)
        <form method="POST" action="{{ route('payroll.generate.run') }}" id="genForm">
            @csrf
            <input type="hidden" name="company_id"      value="{{ $companyId }}">
            <input type="hidden" name="salary_group_id" value="{{ $salaryGroupId }}">
            <input type="hidden" name="year"            value="{{ $year }}">
            <input type="hidden" name="month"           value="{{ $month }}">

            <div class="card overflow-x-auto">
                <table class="grid-tbl text-xs">
                    <thead>
                        <tr style="background:#FEF2F2">
                            <th style="width:36px"><input type="checkbox" id="checkAll" onclick="toggleAll(this)" checked></th>
                            <th>Emp ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Salary Group</th>
                            <th class="text-right">Gross (₹)</th>
                            <th>Bank A/C</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $e)
                            <tr>
                                <td><input type="checkbox" name="emp_ids[]" value="{{ $e->emp_id }}" class="empChk" checked></td>
                                <td>{{ $e->emp_id }}</td>
                                <td>{{ $e->full_name }}</td>
                                <td>{{ $e->department->dept_name ?? '—' }}</td>
                                <td>{{ $e->salary_group->salary_group_name ?? '—' }}</td>
                                <td class="text-right">{{ number_format((float)$e->current_gross, 0) }}</td>
                                <td class="text-xs text-slate-500">{{ $e->bank_account_no ?: '—' }}</td>
                                <td>
                                    @if($existingPayslipEmps->contains($e->emp_id))
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-semibold">PAYSLIP EXISTS</span>
                                        <a href="{{ route('payroll.payslips.show', [$e->emp_id, $year, $month]) }}"
                                           class="ml-1 text-[10px] text-[var(--brand)] underline font-semibold">View →</a>
                                    @else
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">NEW</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-6 text-slate-500">No active employees in this company × salary group.</td></tr>
                        @endforelse
                    </tbody>
                    @if($employees->isNotEmpty())
                        <tfoot>
                            <tr style="background:#F1F5F9;font-weight:600">
                                <td></td>
                                <td colspan="4">Total: {{ $employees->count() }} employees</td>
                                <td class="text-right">₹{{ number_format($employees->sum(fn($e) => (float)$e->current_gross), 0) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @if($employees->isNotEmpty())
                <div class="sticky bottom-0 z-10 bg-white border-t border-[var(--line)] py-3 px-4 mt-3 flex justify-between items-center">
                    <div class="text-xs text-slate-500">
                        <span id="countSelected">{{ $employees->count() }}</span> of {{ $employees->count() }} selected ·
                        Period: {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
                    </div>
                    <button type="submit" class="tb-btn primary" style="background:#16A34A;border-color:#15803D;color:#fff;font-size:14px;padding:10px 22px">▶ Generate Salary for Selected</button>
                </div>
            @endif
        </form>
    @else
        <div class="card p-6 text-center text-slate-500 text-sm">
            Pick a Company, Salary Group and Month above, then click <strong>Get List</strong> to preview employees.
        </div>
    @endif
</div>

{{-- Delete Payroll modal — appears only when group has existing payslips --}}
@if($previewLoaded && $employees->isNotEmpty() && $existingPayslipEmps->isNotEmpty())
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
    <form method="POST" action="{{ route('payroll.generate.delete') }}" onsubmit="return confirm('This will hard-delete every payslip in this group for the selected month. Continue?')"
          style="background:#fff;padding:24px;border-radius:8px;max-width:480px;width:92%;box-shadow:0 10px 40px rgba(0,0,0,0.3)">
        @csrf
        <input type="hidden" name="company_id"      value="{{ $companyId }}">
        <input type="hidden" name="salary_group_id" value="{{ $salaryGroupId }}">
        <input type="hidden" name="year"            value="{{ $year }}">
        <input type="hidden" name="month"           value="{{ $month }}">

        <div class="flex items-center gap-3 mb-3">
            <span style="font-size:32px">🗑️</span>
            <div>
                <h2 class="text-lg font-bold text-red-700">Delete Payroll</h2>
                <p class="text-xs text-slate-500">{{ $existingPayslipEmps->count() }} payslip(s) for this group will be permanently deleted.</p>
            </div>
        </div>

        <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900 mb-3">
            <strong>This action is irreversible.</strong>
            Attendance, manual deductions, VPF, and overtime entries are preserved — you can regenerate after deletion.
        </div>

        <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm with your password :</label>
        <input type="password" name="password" required autofocus
               class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm mb-4"
               placeholder="Your account password"
               autocomplete="current-password">

        <div class="flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('deleteModal').style.display='none'"
                    class="tb-btn">Cancel</button>
            <button type="submit" class="tb-btn primary" style="background:#DC2626;border-color:#B91C1C">
                🗑️ Delete {{ $existingPayslipEmps->count() }} Payslip(s)
            </button>
        </div>
    </form>
</div>
@endif

{{-- Hidden form used by "Generate ALL Groups" — submits to /payroll/generate/all
     with the company_id + year + month picked above, without needing a salary group. --}}
<form method="POST" action="{{ route('payroll.generate.all') }}" id="genAllForm" style="display:none">
    @csrf
    <input type="hidden" name="company_id" id="genAllCompanyId">
    <input type="hidden" name="year"       id="genAllYear">
    <input type="hidden" name="month"      id="genAllMonth">
</form>

<script>
/**
 * Skip group selection — fire payroll for every salary group of the picked
 * company × month in one shot. Picks values straight from the picker form.
 */
function generateAllGroups() {
    const picker = document.getElementById('pickerForm');
    const companyId = picker.querySelector('[name=company_id]').value;
    const month     = picker.querySelector('[name=month]').value;
    const year      = picker.querySelector('[name=year]').value;

    if (!companyId) {
        alert('Please pick a Company first.');
        return;
    }

    const monthLabel = picker.querySelector('[name=month] option:checked').textContent;
    const ok = confirm(
        `Generate salary for ALL salary groups of this company for ${monthLabel} ${year}?\n\n` +
        `This wipes any existing payslips for these employees in this period and recomputes from scratch. ` +
        `Manual deductions, attendance, and OT records are preserved.`
    );
    if (!ok) return;

    document.getElementById('genAllCompanyId').value = companyId;
    document.getElementById('genAllYear').value      = year;
    document.getElementById('genAllMonth').value     = month;
    document.getElementById('genAllForm').submit();
}

function toggleAll(master) {
    document.querySelectorAll('.empChk').forEach(cb => cb.checked = master.checked);
    refreshCount();
}
function refreshCount() {
    const total    = document.querySelectorAll('.empChk').length;
    const selected = document.querySelectorAll('.empChk:checked').length;
    const el = document.getElementById('countSelected');
    if (el) el.textContent = selected;
}
document.querySelectorAll('.empChk').forEach(cb => cb.addEventListener('change', refreshCount));

/**
 * Build an export URL that includes ONLY the checked employees.
 * If everyone is checked, omit the selected_emp_ids[] params (smaller URL).
 */
function exportSelected(format) {
    const checked = Array.from(document.querySelectorAll('.empChk:checked')).map(cb => cb.value);
    const total   = document.querySelectorAll('.empChk').length;

    if (checked.length === 0) {
        alert('Please check at least one employee to export.');
        return;
    }

    const base = "{{ route('payroll.generate') }}";
    const params = new URLSearchParams({
        company_id:      "{{ $companyId }}",
        salary_group_id: "{{ $salaryGroupId }}",
        year:            "{{ $year }}",
        month:           "{{ $month }}",
        get_list:        "1",
        export:          format,
    });
    // Only attach selected_emp_ids[] when the selection is a subset
    if (checked.length < total) {
        checked.forEach(id => params.append('selected_emp_ids[]', id));
    }
    const url = base + '?' + params.toString();
    if (format === 'pdf') {
        window.open(url, '_blank');
    } else {
        window.location = url;
    }
}
</script>
@endsection
