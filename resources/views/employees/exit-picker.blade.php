@extends('layouts.app')
@section('title', 'Pick Employee to Exit')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">
        HR /
        <a href="{{ route('exit-employees') }}" class="hover:underline">Exit Employees</a> /
        <span class="text-slate-900 font-semibold">Pick Employee</span>
    </div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Pick an Employee to Mark as Exited</h1>
        <a href="{{ route('exit-employees') }}" class="tb-btn">← Back</a>
    </div>

    <div class="card p-3 mb-3 bg-amber-50 border-amber-200 text-xs text-amber-900">
        <strong>Tip:</strong> Use the search box to find any active employee, then click "Mark as Exited" to set their last working day, reason, and exclude them from future payroll.
    </div>

    <form method="GET" class="card p-3 mb-3">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by name or Emp ID…" autofocus class="border border-[var(--line)] rounded p-2 text-sm w-72"/>
        <button type="submit" class="tb-btn primary">Search</button>
        @if(request('q'))<a href="{{ route('exit-employees.picker') }}" class="tb-btn">Clear</a>@endif
    </form>

    <div class="card overflow-x-auto">
        <table class="grid-tbl text-xs">
            <thead><tr>
                <th>Emp ID</th><th>Name</th><th>Department</th><th>Date of Joining</th><th>Action</th>
            </tr></thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td>{{ $e->emp_id }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td>{{ $e->department->dept_name ?? '—' }}</td>
                        <td>{{ $e->date_of_joining ?? '—' }}</td>
                        <td>
                            <a href="{{ route('exit-employees.form', $e->emp_id) }}"
                               class="tb-btn primary" style="padding:3px 10px;font-size:11px;background:#DC2626;border-color:#B91C1C">
                                ➜ Mark as Exited
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6 text-slate-500">No active employees match. Try a broader search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif
</div>
@endsection
