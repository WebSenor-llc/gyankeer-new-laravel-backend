@extends('layouts.app')
@section('title', 'Leave Record')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Attendance & Leave / <span class="text-slate-900 font-semibold">Leave Record</span></div>
    <h1 class="text-xl font-bold mb-3">Leave Record (historical applications)</h1>

    <form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
            <select name="status" class="border border-[var(--line)] rounded p-2 text-sm">
                <option value="">All</option>
                @foreach(['Pending','Approved','Rejected','Cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
                @endforeach
            </select></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
            <input type="month" name="month" value="{{ request('month') }}" class="border border-[var(--line)] rounded p-2 text-sm"/></div>
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Emp ID</label>
            <input type="number" name="emp_id" value="{{ request('emp_id') }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px"/></div>
        <button type="submit" class="tb-btn primary">Filter</button>
        @if(request('status') || request('emp_id') || request('month'))
            <a href="{{ route('leave.record') }}" class="tb-btn">Clear</a>
        @endif
    </form>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total Applications</div><div class="text-lg font-bold">{{ $totals['total'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-amber-700 uppercase">Pending</div><div class="text-lg font-bold text-amber-700">{{ $totals['pending'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-green-700 uppercase">Approved</div><div class="text-lg font-bold text-green-700">{{ $totals['approved'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-red-700 uppercase">Rejected</div><div class="text-lg font-bold text-red-700">{{ $totals['rejected'] }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr>
                <th>Emp ID</th><th>Employee</th><th>Type</th>
                <th>From</th><th>To</th><th>Days</th>
                <th>Reason</th><th>Approver</th><th>Status</th><th>Applied At</th><th>Action</th>
            </tr></thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>{{ $r->emp_id }}</td>
                        <td>{{ $r->employee_name ?? '—' }}</td>
                        <td>{{ $r->leave_code ?? $r->leave_type_id ?? '—' }}</td>
                        <td>{{ $r->from_date }}</td>
                        <td>{{ $r->to_date }}</td>
                        <td>{{ $r->days !== null ? rtrim(rtrim(number_format($r->days, 2), '0'), '.') : '—' }}</td>
                        <td class="text-xs">{{ Str::limit($r->reason, 60) }}</td>
                        <td>{{ $r->approver_name ?? '—' }}</td>
                        <td>
                            @php $s = $r->approval_status ?? 'Pending'; @endphp
                            @if($s === 'Approved')<span class="pill pill-ok">{{ $s }}</span>
                            @elseif($s === 'Rejected')<span class="pill pill-bad">{{ $s }}</span>
                            @else<span class="pill pill-warn">{{ $s }}</span>@endif
                        </td>
                        <td class="text-xs text-slate-500">{{ $r->applied_at }}</td>
                        <td>
                            <form method="POST" action="{{ route('leave.destroy', $r->getKey()) }}" onsubmit="return confirm('Delete this leave application?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="pill pill-bad">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center py-6 text-slate-500">
                        No leave applications found.
                        <br><a href="{{ route('leave.apply') }}" class="text-[var(--brand)] font-semibold">Submit a new application →</a>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())<div class="mt-3">{{ $records->links() }}</div>@endif
</div>
@endsection
