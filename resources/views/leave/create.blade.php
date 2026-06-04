@extends('layouts.app')
@section('title', 'Apply for Leave')

@section('content')
<div class="p-4 max-w-2xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        Attendance & Leave /
        <a href="{{ route('leave.record') }}" class="hover:underline">Leave Record</a> /
        <span class="text-slate-900 font-semibold">Apply</span>
    </div>
    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Apply for Leave</h1>
        <a href="{{ route('leave.record') }}" class="tb-btn">View Leave Record →</a>
    </div>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('leave.apply.store') }}" class="card p-5 space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Employee *</label>
            <div class="relative" id="empSearchWrap">
                <input type="hidden" name="emp_id" id="empIdHidden" value="{{ old('emp_id') }}" required>
                <input type="text" id="empSearchInput" autocomplete="off"
                       placeholder="Type to search by ID or name..."
                       value="{{ old('emp_id') ? ($employees->firstWhere('emp_id', old('emp_id'))->emp_id ?? '') . ' — ' . ($employees->firstWhere('emp_id', old('emp_id'))->full_name ?? '') : '' }}"
                       class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]">
                <ul id="empSearchList"
                    class="hidden absolute z-20 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white border border-[var(--line)] rounded-lg shadow-lg text-sm">
                    @foreach($employees as $e)
                        <li data-id="{{ $e->emp_id }}" data-label="{{ $e->emp_id }} — {{ $e->full_name }}"
                            class="px-3 py-1.5 cursor-pointer hover:bg-slate-100">
                            {{ $e->emp_id }} — {{ $e->full_name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div id="balanceBox" class="hidden rounded-lg border border-[var(--line)] bg-slate-50 px-3 py-2 text-sm">
            <div class="text-xs font-semibold text-slate-600 mb-1.5">Leave Balance <span id="balanceFy" class="font-normal text-slate-400"></span></div>
            <div id="balanceBody" class="text-slate-700"></div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Leave Type *</label>
            <select name="leave_type_id" required class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                <option value="">— Select type —</option>
                @foreach($leaveTypes as $t)
                    <option value="{{ $t->leave_type_id }}" @selected(old('leave_type_id') == $t->leave_type_id)>
                        {{ $t->leave_code }} — {{ $t->leave_name ?? $t->leave_code }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">From Date *</label>
                <input type="date" name="from_date" required value="{{ old('from_date', now()->toDateString()) }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">To Date *</label>
                <input type="date" name="to_date" required value="{{ old('to_date', now()->toDateString()) }}" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
            </div>
        </div>

        <div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="half_day_flag" value="1" @if(old('half_day_flag')) checked @endif>
                <span>Half day only</span>
            </label>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Reason</label>
            <textarea name="reason" rows="3" class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">{{ old('reason') }}</textarea>
        </div>

        <p class="text-xs text-slate-500">
            On submit, a leave application is created in <strong>Pending</strong> status. The reporting manager (or HR Admin) approves it from <a href="{{ route('leave.online') }}" class="text-[var(--brand)]">Leave Approvals</a>. Once approved and payroll runs, the system marks those days as paid leave (no LOP deduction).
        </p>

        <div class="flex justify-end gap-2 pt-3 border-t border-[var(--line)]">
            <a href="{{ route('leave.record') }}" class="tb-btn">Cancel</a>
            <button type="submit" class="tb-btn primary">Submit Application</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrap   = document.getElementById('empSearchWrap');
    const input  = document.getElementById('empSearchInput');
    const hidden = document.getElementById('empIdHidden');
    const list   = document.getElementById('empSearchList');
    if (!wrap || !input || !hidden || !list) return;
    const items = Array.from(list.querySelectorAll('li'));

    const balanceBox  = document.getElementById('balanceBox');
    const balanceBody = document.getElementById('balanceBody');
    const balanceFy   = document.getElementById('balanceFy');
    const balanceUrl  = "{{ route('leave.balance.json', ['empId' => '__ID__']) }}";

    const loadBalance = (empId) => {
        if (!empId) { balanceBox.classList.add('hidden'); return; }
        balanceBody.innerHTML = '<span class="text-slate-400">Loading…</span>';
        balanceBox.classList.remove('hidden');
        fetch(balanceUrl.replace('__ID__', empId), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                balanceFy.textContent = data.fy ? '(FY ' + (data.fy - 1) + '-' + String(data.fy).slice(-2) + ')' : '';
                if (!data.balances || !data.balances.length) {
                    balanceBody.innerHTML = '<span class="text-slate-400">No balance records found.</span>';
                    return;
                }
                balanceBody.innerHTML = data.balances.map(b =>
                    '<span class="inline-block mr-4"><strong>' + b.leave_code + '</strong>: ' +
                    b.closing + ' available <span class="text-slate-400">(' + b.availed + ' used)</span></span>'
                ).join('');
            })
            .catch(() => { balanceBody.innerHTML = '<span class="text-red-500">Could not load balance.</span>'; });
    };

    const filter = () => {
        const q = input.value.trim().toLowerCase();
        let any = false;
        items.forEach(li => {
            const match = !q || li.dataset.label.toLowerCase().includes(q);
            li.classList.toggle('hidden', !match);
            if (match) any = true;
        });
        list.classList.toggle('hidden', !any);
    };

    // On focus, show ALL employees and select the current text so the user can
    // immediately pick a different one or type to narrow.
    input.addEventListener('focus', () => {
        items.forEach(li => li.classList.remove('hidden'));
        list.classList.remove('hidden');
        input.select();
    });
    input.addEventListener('input', () => { hidden.value = ''; filter(); balanceBox.classList.add('hidden'); });

    items.forEach(li => {
        li.addEventListener('mousedown', e => {
            e.preventDefault();
            hidden.value = li.dataset.id;
            input.value = li.dataset.label;
            list.classList.add('hidden');
            loadBalance(li.dataset.id);
        });
    });

    // Preselected employee (e.g. after a validation error) — load its balance.
    if (hidden.value) loadBalance(hidden.value);

    document.addEventListener('click', e => {
        if (!wrap.contains(e.target)) list.classList.add('hidden');
    });

    // Prevent double-submit (double-click) creating duplicate applications.
    const form = wrap.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; btn.textContent = 'Submitting…'; }
        });
    }
});
</script>
@endsection
