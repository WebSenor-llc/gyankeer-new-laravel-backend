@extends('layouts.app')
@section('title', 'Headcount Report')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">Headcount</span></div>
    <h1 class="text-xl font-bold mb-3">Headcount Report</h1>

    <div class="card p-4 mb-4" style="background:#FEF2F2;border-color:#FCA5A5">
        <div class="text-[11px] text-red-700 uppercase font-semibold">Total Active Employees</div>
        <div class="text-3xl font-bold text-red-700">{{ $total }}</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card overflow-x-auto">
            <div class="p-3 border-b border-[var(--line)] font-semibold">By Department</div>
            <table class="grid-tbl">
                <thead><tr><th>Department</th><th>Headcount</th></tr></thead>
                <tbody>
                    @forelse($byDept as $d)
                        <tr>
                            <td>{{ $d->department->dept_name ?? ('Dept #' . $d->dept_id) }}</td>
                            <td>{{ $d->c }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center py-6 text-slate-500">No employees yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card overflow-x-auto">
            <div class="p-3 border-b border-[var(--line)] font-semibold">By Type</div>
            <table class="grid-tbl">
                <thead><tr><th>Type</th><th>Headcount</th></tr></thead>
                <tbody>
                    @forelse($byType as $type => $c)
                        <tr><td>{{ $type ?? 'Unknown' }}</td><td>{{ $c }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center py-6 text-slate-500">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
