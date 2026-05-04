@extends('layouts.app')
@section('title', 'Mark Attendance')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">
        Attendance & Leave /
        <a href="{{ route('attendance.daily') }}" class="hover:underline">Daily Attendance</a> /
        <span class="text-slate-900 font-semibold">Mark Attendance</span>
    </div>
    <h1 class="text-xl font-bold mb-1">Mark Attendance Manually</h1>
    <p class="text-xs text-slate-500 mb-3">Pick a date, set status per employee. Use the search box to filter. Quick-action buttons set every visible row at once.</p>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('attendance.manual.mark') }}">
        @csrf
        <div class="card p-3 mb-3 flex flex-wrap gap-3 items-end">
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Date *</label>
                <input type="date" name="date" required value="{{ request('date', today()->toDateString()) }}" class="border border-[var(--line)] rounded p-2 text-sm"/></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
                <input type="search" id="empSearch" placeholder="Filter list&hellip;" class="border border-[var(--line)] rounded p-2 text-sm" style="width:240px"/></div>
            <div class="flex-1"></div>
            <button type="button" onclick="setAll('Present')" class="tb-btn">Mark all Present</button>
            <button type="button" onclick="setAll('Weekly Off')" class="tb-btn">Mark all Weekly Off</button>
            <button type="button" onclick="setAll('')" class="tb-btn">Clear</button>
            <button type="submit" class="tb-btn primary">Save Attendance</button>
        </div>

        <div class="card overflow-x-auto">
            <table class="grid-tbl" id="attTable">
                <thead><tr><th>Emp ID</th><th>Name</th><th>Department</th><th>Shift</th><th style="width:240px">Status</th><th style="width:200px">Reason / Note</th></tr></thead>
                <tbody>
                    @foreach($employees as $e)
                        <tr data-search="{{ strtolower(($e->emp_id).' '.($e->full_name).' '.($e->department->dept_name ?? '')) }}">
                            <td>{{ $e->emp_id }}</td>
                            <td>{{ $e->full_name }}</td>
                            <td>{{ $e->department->dept_name ?? '—' }}</td>
                            <td>{{ $e->shift->shift_name ?? '—' }}</td>
                            <td>
                                <select name="status[{{ $e->emp_id }}]" class="att-status border border-[var(--line)] rounded p-1.5 text-sm w-full">
                                    <option value="">— Skip —</option>
                                    <option value="Present">Present</option>
                                    <option value="Absent">Absent</option>
                                    <option value="On Leave">On Leave</option>
                                    <option value="On Duty">On Duty</option>
                                    <option value="Half Day">Half Day</option>
                                    <option value="Weekly Off">Weekly Off</option>
                                    <option value="Holiday">Holiday</option>
                                </select>
                            </td>
                            <td><input type="text" name="reason[{{ $e->emp_id }}]" class="border border-[var(--line)] rounded p-1.5 text-sm w-full" placeholder="optional"/></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
function setAll(val){
    document.querySelectorAll('#attTable tbody tr').forEach(tr => {
        if (tr.style.display !== 'none') {
            const sel = tr.querySelector('.att-status');
            if (sel) sel.value = val;
        }
    });
}
document.getElementById('empSearch').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('#attTable tbody tr').forEach(tr => {
        tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
    });
});
</script>
@endsection
