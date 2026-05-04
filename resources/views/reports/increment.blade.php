@extends('layouts.app')
@section('title', 'Increment Report')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">Increment</span></div>
    <h1 class="text-xl font-bold mb-3">Increment Report</h1>
    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Name</th><th>Last Increment</th><th>%</th><th>Old Gross</th><th>New Gross</th></tr></thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td>{{ $e->emp_id }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td>{{ $e->last_increment_date }}</td>
                        <td>{{ $e->last_increment_pct }}%</td>
                        <td>&#8377;{{ number_format($e->last_increment_old_gross, 2) }}</td>
                        <td>&#8377;{{ number_format($e->last_increment_new_gross, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6 text-slate-500">No increments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif
</div>
@endsection
