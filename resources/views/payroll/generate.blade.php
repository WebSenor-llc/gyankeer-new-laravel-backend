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

            {{-- Multi-select Salary Groups (custom Tailwind + vanilla JS) --}}
            <div class="col-span-3 relative" id="sgWrap">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Salary Groups :</label>
                <button type="button" id="sgToggle"
                        class="w-full border border-[var(--line)] rounded p-2 text-sm text-left bg-white flex items-center justify-between">
                    <span id="sgLabel" class="truncate text-slate-700">— Select Groups —</span>
                    <span class="text-slate-400 ml-2">▾</span>
                </button>
                <div id="sgPanel"
                     class="hidden absolute z-30 left-0 right-0 mt-1 bg-white border border-[var(--line)] rounded shadow-lg max-h-72 overflow-y-auto">
                    @if($salaryGroups->isEmpty())
                        <div class="px-3 py-2 text-xs text-slate-500">No salary groups for this company.</div>
                    @else
                        <label class="flex items-center gap-2 px-3 py-2 border-b border-slate-100 bg-slate-50 text-sm font-semibold cursor-pointer">
                            <input type="checkbox" id="sgAll"> Select All
                        </label>
                        @foreach($salaryGroups as $g)
                            <label class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50 text-sm cursor-pointer">
                                <input type="checkbox" class="sgChk" value="{{ $g->salary_group_id }}"
                                       data-label="{{ $g->salary_group_name }}"
                                       @checked(in_array($g->salary_group_id, $salaryGroupIds ?? []))>
                                <span>{{ $g->salary_group_name }}</span>
                            </label>
                        @endforeach
                    @endif
                </div>
                <div id="sgHidden">
                    @foreach($salaryGroupIds ?? [] as $sgid)
                        <input type="hidden" name="salary_group_ids[]" value="{{ $sgid }}">
                    @endforeach
                </div>
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
                <button type="button" onclick="document.getElementById('genForm').submit()" class="tb-btn primary" style="background:#16A34A;border-color:#15803D;color:#fff" @disabled(!$previewLoaded)>Generate Salary</button>
                @if($previewLoaded && $employees->isNotEmpty())
                    <a href="{{ route('payroll.runs.index') }}" class="tb-btn">View Runs</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Employee list (preview after Get List) — one card per salary group --}}
    @if($previewLoaded)
        <form method="POST" action="{{ route('payroll.generate.run') }}" id="genForm">
            @csrf
            <input type="hidden" name="company_id" value="{{ $companyId }}">
            <input type="hidden" name="year"       value="{{ $year }}">
            <input type="hidden" name="month"      value="{{ $month }}">
            @foreach($salaryGroupIds as $sgid)
                <input type="hidden" name="salary_group_ids[]" value="{{ $sgid }}">
            @endforeach

            @forelse($orderedGroups as $gid => $group)
                @include('payroll.partials.generate_group_card', [
                    'group'               => $group,
                    'employees'           => $employeesByGroup[$gid] ?? collect(),
                    'companyId'           => $companyId,
                    'year'                => $year,
                    'month'               => $month,
                    'existingPayslipEmps' => $existingPayslipEmps,
                ])
            @empty
                <div class="card p-6 text-center text-slate-500 text-sm">
                    No active employees in the selected company × salary group(s).
                </div>
            @endforelse

            @if($employees->isNotEmpty())
                <div class="sticky bottom-0 z-10 bg-white border-t border-[var(--line)] py-3 px-4 mt-3 flex justify-between items-center">
                    <div class="text-xs text-slate-500">
                        <span id="countSelected">{{ $employees->count() }}</span> of {{ $employees->count() }} selected across {{ $orderedGroups->count() }} group(s) ·
                        Period: {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
                    </div>
                    <button type="submit" class="tb-btn primary" style="background:#16A34A;border-color:#15803D;color:#fff;font-size:14px;padding:10px 22px">▶ Generate Salary for Selected</button>
                </div>
            @endif
        </form>
    @else
        <div class="card p-6 text-center text-slate-500 text-sm">
            Pick a Company, one or more Salary Groups and Month above, then click <strong>Get List</strong> to preview employees.
        </div>
    @endif
</div>

{{-- Delete Payroll modal — populated per-group via openDeleteModal(gid) --}}
@if($previewLoaded)
<div id="deleteModal" class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center" style="align-items:center;justify-content:center">
    <form method="POST" action="{{ route('payroll.generate.delete') }}" onsubmit="return confirm('This will hard-delete every payslip in this group for the selected month. Continue?')"
          style="background:#fff;padding:24px;border-radius:8px;max-width:480px;width:92%;box-shadow:0 10px 40px rgba(0,0,0,0.3)">
        @csrf
        <input type="hidden" name="company_id"      value="{{ $companyId }}">
        <input type="hidden" name="salary_group_id" id="deleteGroupId" value="">
        <input type="hidden" name="year"            value="{{ $year }}">
        <input type="hidden" name="month"           value="{{ $month }}">

        <div class="flex items-center gap-3 mb-3">
            <span style="font-size:32px">🗑️</span>
            <div>
                <h2 class="text-lg font-bold text-red-700">Delete Payroll — <span id="deleteGroupName"></span></h2>
                <p class="text-xs text-slate-500">All payslips for this group × period will be permanently deleted.</p>
            </div>
        </div>

        <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900 mb-3">
            <strong>This action is irreversible.</strong>
            Attendance, manual deductions, VPF, and overtime entries are preserved — you can regenerate after deletion.
        </div>

        <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm with your password :</label>
        <input type="password" name="password" required
               class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm mb-4"
               placeholder="Your account password"
               autocomplete="current-password">

        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeDeleteModal()" class="tb-btn">Cancel</button>
            <button type="submit" class="tb-btn primary" style="background:#DC2626;border-color:#B91C1C">🗑️ Delete</button>
        </div>
    </form>
</div>
@endif

<script>
// ===== Multi-select dropdown =====
(function () {
    const wrap = document.getElementById('sgWrap');
    if (!wrap) return;
    const toggle = document.getElementById('sgToggle'),
          panel  = document.getElementById('sgPanel'),
          label  = document.getElementById('sgLabel'),
          all    = document.getElementById('sgAll'),
          hidden = document.getElementById('sgHidden'),
          boxes  = Array.from(document.querySelectorAll('.sgChk'));

    function syncLabel() {
        const picked = boxes.filter(b => b.checked);
        if (!picked.length) {
            label.textContent = '— Select Groups —';
            if (all) all.checked = false;
            return;
        }
        const names = picked.map(b => b.dataset.label);
        label.textContent = names.length <= 2
            ? names.join(', ')
            : names.slice(0,2).join(', ') + ' (+' + (names.length - 2) + ' more)';
        if (all) all.checked = picked.length === boxes.length;
    }
    function syncHidden() {
        hidden.innerHTML = '';
        boxes.filter(b => b.checked).forEach(b => {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = 'salary_group_ids[]'; i.value = b.value;
            hidden.appendChild(i);
        });
    }
    toggle.addEventListener('click', (e) => { e.stopPropagation(); panel.classList.toggle('hidden'); });
    document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) panel.classList.add('hidden'); });
    if (all) all.addEventListener('change', () => { boxes.forEach(b => b.checked = all.checked); syncLabel(); syncHidden(); });
    boxes.forEach(b => b.addEventListener('change', () => { syncLabel(); syncHidden(); }));
    syncLabel();
})();

// ===== Per-group employee selection =====
function toggleGroup(master, gid) {
    document.querySelectorAll('.empChk-' + gid).forEach(cb => cb.checked = master.checked);
    refreshCount(gid);
}
function refreshCount(gid) {
    if (gid) {
        const sel = document.querySelectorAll('.empChk-' + gid + ':checked').length;
        const el  = document.querySelector('.grpCount-' + gid);
        if (el) el.textContent = sel;
    }
    const total = document.getElementById('countSelected');
    if (total) total.textContent = document.querySelectorAll('.empChk:checked').length;
}
document.querySelectorAll('.empChk').forEach(cb => {
    cb.addEventListener('change', () => refreshCount(cb.dataset.groupId));
});

// ===== Per-group export =====
function exportSelected(format, gid) {
    const checked = Array.from(document.querySelectorAll('.empChk-' + gid + ':checked')).map(cb => cb.value);
    const total   = document.querySelectorAll('.empChk-' + gid).length;
    if (!checked.length) { alert('Please check at least one employee to export.'); return; }
    const params = new URLSearchParams({
        company_id:      "{{ $companyId }}",
        salary_group_id: gid,
        year:            "{{ $year }}",
        month:           "{{ $month }}",
        get_list:        "1",
        export:          format,
    });
    if (checked.length < total) checked.forEach(id => params.append('selected_emp_ids[]', id));
    const url = "{{ route('payroll.generate') }}?" + params.toString();
    if (format === 'pdf') window.open(url, '_blank'); else window.location = url;
}

// ===== Per-group delete modal =====
window.__groupNames = @json(($orderedGroups ?? collect())->mapWithKeys(fn($g, $k) => [$k => $g->salary_group_name]));
function openDeleteModal(gid) {
    const idEl = document.getElementById('deleteGroupId');
    const nmEl = document.getElementById('deleteGroupName');
    if (idEl) idEl.value = gid;
    if (nmEl) nmEl.textContent = window.__groupNames[gid] || '';
    const m = document.getElementById('deleteModal');
    if (!m) return;
    m.classList.remove('hidden'); m.classList.add('flex');
    m.style.display = 'flex';
}
function closeDeleteModal() {
    const m = document.getElementById('deleteModal');
    if (!m) return;
    m.classList.add('hidden'); m.classList.remove('flex');
    m.style.display = 'none';
}
</script>
@endsection
