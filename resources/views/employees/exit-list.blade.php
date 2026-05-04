@extends('layouts.app')
@section('title', 'Exit Employees')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">HR / <span class="text-slate-900 font-semibold">Exit Employees</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Exit Employees</h1>
        <a href="{{ route('exit-employees.picker') }}" class="tb-btn primary" style="background:#DC2626;border-color:#B91C1C">
            ➜ Mark Existing Employee as Exited
        </a>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-green-700 uppercase">Active</div><div class="text-lg font-bold text-green-700">{{ $totalActive }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-red-700 uppercase">Exited (all time)</div><div class="text-lg font-bold text-red-700">{{ $totalExited }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total</div><div class="text-lg font-bold">{{ $totalActive + $totalExited }}</div></div>
    </div>

    <form method="GET" class="card p-3 mb-3">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by name or Emp ID…" class="border border-[var(--line)] rounded p-2 text-sm w-72"/>
        <button type="submit" class="tb-btn primary">Search</button>
        @if(request('q'))<a href="{{ route('exit-employees') }}" class="tb-btn">Clear</a>@endif
    </form>

    <div class="card overflow-x-auto">
        <table class="grid-tbl text-xs">
            <thead><tr>
                <th>Emp ID</th><th>Name</th><th>Department</th><th>Salary Group</th>
                <th>Date of Joining</th><th>Date of Relieving</th><th>Reason</th><th>Notice?</th><th>Notes</th><th>Actions</th>
            </tr></thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td>{{ $e->emp_id }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td>{{ $e->department->dept_name ?? '—' }}</td>
                        <td>{{ $e->salary_group->salary_group_name ?? '—' }}</td>
                        <td>{{ $e->date_of_joining ?? '—' }}</td>
                        <td><strong>{{ $e->date_of_relieving ?? '—' }}</strong></td>
                        <td>
                            @php $reason = $e->exit_reason ?? $e->employment_status; @endphp
                            <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold
                                @if($reason==='Resigned')      bg-amber-100 text-amber-800
                                @elseif($reason==='Terminated') bg-red-100 text-red-800
                                @elseif($reason==='Retired')   bg-blue-100 text-blue-800
                                @elseif($reason==='Death')     bg-slate-200 text-slate-800
                                @else                          bg-slate-100 text-slate-700 @endif">
                                {{ $reason }}
                            </span>
                        </td>
                        <td>{{ $e->notice_served_flag ? 'Yes' : 'No' }}</td>
                        <td class="text-xs text-slate-500" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $e->exit_notes }}</td>
                        <td class="flex gap-1">
                            <a href="{{ route('exit-employees.form', $e->emp_id) }}" class="tb-btn" style="padding:2px 8px;font-size:11px">Edit Exit</a>
                            <form method="POST" action="{{ route('exit-employees.reactivate', $e->emp_id) }}" onsubmit="return confirm('Reactivate {{ $e->full_name }}? They will be eligible for payroll again.')" class="inline">
                                @csrf
                                <button type="submit" class="tb-btn" style="padding:2px 8px;font-size:11px;background:#16A34A;color:#fff;border-color:#15803D">Reactivate</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center py-6 text-slate-500">No exited employees yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif
</div>
@endsection
