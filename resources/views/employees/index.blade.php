@extends('layouts.app')
@section('title','Manage Employee')
@section('content')

<div class="text-xs mb-2 text-slate-500">HR / Master Config / <span class="text-slate-900 font-semibold">Manage Employee</span></div>

<div class="flex items-center justify-between mb-3">
    <h1 class="text-xl font-bold">Manage Employee</h1>
    <div class="flex gap-2">
        <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data" class="inline">
            @csrf
            <button class="tb-btn">⤒ Bulk Import</button>
        </form>
        <a href="{{ route('employees.create') }}" class="tb-btn primary">+ Add New Employee</a>
    </div>
</div>

<form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by name / EMP ID / TP Code / PAN / UAN…"
           class="border border-[var(--line)] rounded p-2 text-sm flex-1 min-w-60"/>
    <select name="dept_id" class="border border-[var(--line)] rounded p-2 text-sm">
        <option value="">All Departments</option>
        @foreach($departments as $d)
            <option value="{{ $d->dept_id }}" @selected(request('dept_id') == $d->dept_id)>{{ $d->dept_name }}</option>
        @endforeach
    </select>
    <select name="emp_type" class="border border-[var(--line)] rounded p-2 text-sm">
        <option value="">All Types</option>
        <option value="ST" @selected(request('emp_type') === 'ST')>ST — Staff</option>
        <option value="SB" @selected(request('emp_type') === 'SB')>SB — Sub-Staff</option>
        <option value="WK" @selected(request('emp_type') === 'WK')>WK — Worker</option>
    </select>
    <select name="status" class="border border-[var(--line)] rounded p-2 text-sm">
        <option value="">All Status</option>
        <option value="Active">Active</option>
        <option value="Notice">On Notice</option>
        <option value="Exited">Exited</option>
    </select>
    <button type="submit" class="tb-btn primary">🔍 Filter</button>
</form>

<div class="card overflow-hidden">
    <table class="grid-tbl">
        <thead>
            <tr>
                <th>Image</th><th>EMP ID</th><th>Name</th><th>T.P. Code</th>
                <th>Father / Husband</th><th>Type</th><th>Department</th><th>Designation</th>
                <th>Salary</th><th>Gross</th><th>Company</th><th>Status</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $e)
            <tr class="hover:bg-slate-50">
                <td>
                    @if($e->photo_path)
                        <img src="{{ asset('storage/'.$e->photo_path) }}" alt=""
                             class="w-9 h-9 rounded-full object-cover border border-[var(--line)]">
                    @else
                        <div class="w-9 h-9 rounded-full grad-red text-white text-[11px] font-bold flex items-center justify-center">{{ strtoupper(substr($e->first_name, 0, 1).substr($e->last_name, 0, 1)) }}</div>
                    @endif
                </td>
                <td class="font-mono">{{ $e->emp_id }}</td>
                <td><a href="{{ route('employees.show', $e->emp_id) }}" class="text-[var(--brand)] hover:underline">{{ $e->full_name }}</a></td>
                <td class="font-mono text-[11px]">{{ $e->third_party_code }}</td>
                <td>
                    @php
                        $isFemaleMarried = $e->marital_status === 'Married' && in_array(strtolower($e->gender ?? ''), ['female','f']);
                        $relName   = $e->relative_name ?: ($isFemaleMarried ? ($e->spouse_name ?: $e->fathers_name) : ($e->fathers_name ?: $e->spouse_name));
                        $relPrefix = $e->relation_prefix ?: ($isFemaleMarried && $e->spouse_name ? 'W/O' : ($e->fathers_name ? 'S/O' : ''));
                    @endphp
                    {{ trim(($relPrefix ? $relPrefix.' ' : '').($relName ?: '—')) }}
                </td>
                <td><span class="pill pill-{{ $e->employee_type === 'ST' ? 'info' : ($e->employee_type === 'SB' ? 'warn' : 'muted') }}">{{ $e->employee_type }}</span></td>
                <td>{{ $e->department?->dept_name }}</td>
                <td>{{ $e->designation?->designation_name }}</td>
                <td class="font-mono">₹{{ number_format($e->current_gross, 0) }}</td>
                <td class="font-mono font-semibold">₹{{ number_format($e->current_gross, 0) }}</td>
                <td class="text-[11px] text-slate-500">{{ $e->company?->company_name }}</td>
                <td><span class="pill pill-{{ $e->employment_status === 'Active' ? 'ok' : 'warn' }}">{{ $e->employment_status }}</span></td>
                <td>
                    <a href="{{ route('employees.show', $e->emp_id) }}" class="text-[var(--brand)] text-xs">✎ Edit</a>
                    <a href="{{ route('manage-salary.config', $e->emp_id) }}" class="text-[var(--brand)] text-xs ml-2">⚙ Salary</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="13" class="text-center py-8 text-slate-400">No employees match your filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $employees->withQueryString()->links() }}</div>
</div>

<div class="text-[11px] text-slate-500 mt-2">Type legend: <b>ST</b> Staff · <b>SB</b> Sub-Staff · <b>WK</b> Worker</div>

@endsection
