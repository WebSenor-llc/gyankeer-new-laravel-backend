@extends('layouts.app')
@section('title', 'View Reporting Hierarchy')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Attendance & Leave / <span class="text-slate-900 font-semibold">View Reporting</span></div>
    <h1 class="text-xl font-bold mb-3">Reporting Hierarchy</h1>
    <p class="text-xs text-slate-500 mb-3">Employees grouped by department; reports-to relationships shown where set.</p>
    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Name</th><th>Department</th><th>Designation</th><th>Reports To (Emp ID)</th></tr></thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td>{{ $e->emp_id }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td>{{ $e->department->dept_name ?? '—' }}</td>
                        <td>{{ $e->designation->designation_name ?? '—' }}</td>
                        <td>{{ $e->reports_to_emp_id ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6 text-slate-500">No active employees.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
