@extends('layouts.app')
@section('title', 'Set Reporting Manager')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Attendance & Leave / <span class="text-slate-900 font-semibold">Set Reporting Manager</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Set Reporting Manager</h1>
        <a href="{{ route('attendance.view-reporting') }}" class="tb-btn">View Hierarchy</a>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <p class="text-xs text-slate-500 mb-3">Type the manager's emp ID (autocomplete will suggest names) and click Save.</p>

    {{-- Single shared datalist of managers (renders ONCE — not per-row) --}}
    <datalist id="managerList">
        @foreach($managers as $m)
            <option value="{{ $m->emp_id }}">{{ $m->emp_id }} — {{ $m->full_name }}</option>
        @endforeach
    </datalist>

    {{-- Filter form (separate from save form) --}}
    <form method="GET" class="card p-3 mb-3 flex flex-wrap gap-3 items-end">
        <input type="search" name="search_q" value="{{ request('search_q') }}" placeholder="Filter employees by name or emp ID…" class="border border-[var(--line)] rounded p-2 text-sm flex-1 max-w-md"/>
        <button type="submit" class="tb-btn">Apply Filter</button>
        @if(request('search_q'))
            <a href="{{ route('attendance.set-reporting') }}" class="tb-btn">Clear</a>
        @endif
    </form>

    {{-- Save form --}}
    <form method="POST" action="{{ route('attendance.set-reporting.save') }}">
        @csrf
        <div class="card overflow-x-auto">
            <table class="grid-tbl">
                <thead><tr>
                    <th>Emp ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Current Manager</th>
                    <th style="min-width:240px">Reports To (type emp ID)</th>
                </tr></thead>
                <tbody>
                    @foreach($employees as $e)
                        <tr>
                            <td>{{ $e->emp_id }}</td>
                            <td>{{ $e->full_name }}</td>
                            <td>{{ $e->department->dept_name ?? '—' }}</td>
                            <td class="text-xs text-slate-500">
                                @if($e->reports_to_emp_id)
                                    {{ $managers->firstWhere('emp_id', $e->reports_to_emp_id)->full_name ?? '#'.$e->reports_to_emp_id }}
                                @else
                                    <em>none</em>
                                @endif
                            </td>
                            <td>
                                <input type="text" list="managerList" name="manager[{{ $e->emp_id }}]"
                                       value="{{ $e->reports_to_emp_id }}"
                                       placeholder="Type emp ID…"
                                       class="block w-full border border-[var(--line)] rounded p-1.5 text-sm">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif

        <div class="sticky bottom-0 bg-white border-t border-[var(--line)] py-3 px-4 mt-3 flex justify-between items-center">
            <div class="text-xs text-slate-500">{{ $employees->total() }} employees · 50 per page · Autocomplete from all {{ $managers->count() }} managers</div>
            <button type="submit" class="tb-btn primary">💾 Save Reporting Structure</button>
        </div>
    </form>
</div>
@endsection
