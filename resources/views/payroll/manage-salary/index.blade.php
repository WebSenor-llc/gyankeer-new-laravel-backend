@extends('layouts.app')
@section('title', 'Manage Salary')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Payroll / <span class="text-slate-900 font-semibold">Manage Salary (Salary Configuration)</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold flex items-center gap-2"><span>⚙️</span> Salary Configuration</h1>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    {{-- Filter form --}}
    <form method="GET" class="card p-3 mb-3 flex flex-wrap gap-2 items-end">
        <input type="search" name="search_q" value="{{ request('search_q') }}" placeholder="Search by name / Emp ID / TP Code / Father…" class="border border-[var(--line)] rounded p-2 text-sm flex-1 min-w-60"/>
        <select name="dept_id" class="border border-[var(--line)] rounded p-2 text-sm">
            <option value="">All Departments</option>
            @foreach($departments as $d)
                <option value="{{ $d->dept_id }}" @selected(request('dept_id') == $d->dept_id)>{{ $d->dept_name }}</option>
            @endforeach
        </select>
        <select name="salary_group_id" class="border border-[var(--line)] rounded p-2 text-sm" style="min-width:180px">
            <option value="">All Salary Groups</option>
            @foreach($salaryGroups as $g)
                <option value="{{ $g->salary_group_id }}" @selected(request('salary_group_id') == $g->salary_group_id)>{{ $g->salary_group_name }}</option>
            @endforeach
        </select>
        <select name="emp_type" class="border border-[var(--line)] rounded p-2 text-sm">
            <option value="">All Types</option>
            <option value="ST" @selected(request('emp_type')=='ST')>ST — Staff</option>
            <option value="SB" @selected(request('emp_type')=='SB')>SB — Sub-Staff</option>
            <option value="WK" @selected(request('emp_type')=='WK')>WK — Worker</option>
        </select>
        <button type="submit" class="tb-btn primary">🔍 Filter</button>
        @if(request('search_q') || request('dept_id') || request('salary_group_id') || request('emp_type'))
            <a href="{{ route('manage-salary.index') }}" class="tb-btn">Clear</a>
        @endif
    </form>

    {{-- Listing — SUGAM-style columns --}}
    <div class="card overflow-x-auto">
        <table class="grid-tbl text-xs">
            <thead style="background:#FEF2F2"><tr>
                <th>E.</th>
                <th>T.P.</th>
                <th>Name</th>
                <th>F. Name</th>
                <th>Type</th>
                <th>DOB</th>
                <th>DOJ</th>
                <th>JobDesc</th>
                <th>Salary Group</th>
                <th>Department</th>
                <th class="text-right">Gross</th>
                <th>Action</th>
            </tr></thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td>{{ $e->emp_id }}</td>
                        <td class="text-xs text-slate-500">{{ $e->third_party_code }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td class="text-xs text-slate-600">{{ $e->fathers_name }}</td>
                        <td>{{ $e->employee_type }}</td>
                        <td>{{ $e->dob ? \Carbon\Carbon::parse($e->dob)->format('d-M-Y') : '—' }}</td>
                        <td>{{ $e->date_of_joining ? \Carbon\Carbon::parse($e->date_of_joining)->format('d-M-Y') : '—' }}</td>
                        <td class="text-xs">{{ $e->designation->designation_name ?? '—' }}</td>
                        <td class="text-xs">{{ $e->salary_group->salary_group_name ?? '—' }}</td>
                        <td class="text-xs">{{ $e->department->dept_name ?? '—' }}</td>
                        <td class="text-right">&#8377;{{ number_format((float)$e->current_gross, 2) }}</td>
                        <td>
                            <a href="{{ route('manage-salary.config', $e->emp_id) }}" class="tb-btn primary" style="padding:2px 10px;font-size:11px">⚙ Configure</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center py-6 text-slate-500">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif
    <p class="text-[11px] text-slate-500 mt-2">{{ $employees->total() }} employees · Click "⚙ Configure" to edit an employee's salary structure (basic, allowances, PF/ESI flags, bank, etc.)</p>
</div>
@endsection
