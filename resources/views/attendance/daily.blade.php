@extends('layouts.app')
@section('title', 'Daily Attendance')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Attendance & Leave / <span class="text-slate-900 font-semibold">Daily Attendance</span></div>
    <h1 class="text-xl font-bold mb-3">Daily Attendance &mdash; {{ $date }}</h1>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <form method="GET" class="card p-3 mb-4 flex gap-2 items-end">
        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Date</label>
            <input type="date" name="date" value="{{ $date }}" class="border border-[var(--line)] rounded p-2 text-sm"/></div>
        <button type="submit" class="tb-btn primary">Apply</button>
        <a href="{{ route('attendance.manual') }}?date={{ $date }}" class="tb-btn">+ Mark Attendance</a>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total</div><div class="text-lg font-bold">{{ $totals['total'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase text-green-700">Present</div><div class="text-lg font-bold text-green-700">{{ $totals['present'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase text-amber-700">On Leave</div><div class="text-lg font-bold text-amber-700">{{ $totals['leave'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase text-blue-700">On Duty</div><div class="text-lg font-bold text-blue-700">{{ $totals['duty'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase text-red-700">Absent</div><div class="text-lg font-bold text-red-700">{{ $totals['absent'] }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Name</th><th>Shift</th><th>In Time</th><th>Out Time</th><th>Hours</th><th>OT Hrs</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>{{ $r->emp_id }}</td>
                        <td>{{ $r->employee_name ?? '—' }}</td>
                        <td>{{ $r->shift_name ?? '—' }}</td>
                        <td>{{ $r->in_time ?? '—' }}</td>
                        <td>{{ $r->out_time ?? '—' }}</td>
                        <td>{{ $r->hours_worked ?? '—' }}</td>
                        <td>{{ $r->ot_hours ?? '0' }}</td>
                        <td>
                            @php $s = $r->status ?? 'Unknown'; @endphp
                            @if($s === 'Present')<span class="pill pill-ok">{{ $s }}</span>
                            @elseif($s === 'Absent')<span class="pill pill-bad">{{ $s }}</span>
                            @elseif(in_array($s, ['On Leave','On Duty','Holiday','Weekly Off']))<span class="pill pill-info">{{ $s }}</span>
                            @else<span class="pill pill-warn">{{ $s }}</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-6 text-slate-500">
                        No attendance records for {{ $date }}.
                        <br><a href="{{ route('attendance.manual') }}?date={{ $date }}" class="text-[var(--brand)] font-semibold">+ Mark attendance manually &rarr;</a>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())<div class="mt-3">{{ $records->links() }}</div>@endif
</div>
@endsection
