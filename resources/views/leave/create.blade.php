@extends('layouts.app')
@section('title', 'Apply for Leave')

@section('content')
<div class="p-4 max-w-2xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        Attendance & Leave /
        <a href="{{ route('leave.record') }}" class="hover:underline">Leave Record</a> /
        <span class="text-slate-900 font-semibold">Apply</span>
    </div>
    <h1 class="text-xl font-bold mb-3">Apply for Leave</h1>

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

    input.addEventListener('focus', filter);
    input.addEventListener('input', () => { hidden.value = ''; filter(); });

    items.forEach(li => {
        li.addEventListener('mousedown', e => {
            e.preventDefault();
            hidden.value = li.dataset.id;
            input.value = li.dataset.label;
            list.classList.add('hidden');
        });
    });

    document.addEventListener('click', e => {
        if (!wrap.contains(e.target)) list.classList.add('hidden');
    });
});
</script>
@endsection
