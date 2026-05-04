@extends('layouts.app')
@section('title', 'Exit Report')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">Exit</span></div>
    <h1 class="text-xl font-bold mb-3">Exit Report</h1>
    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Name</th><th>Date of Joining</th><th>Date of Leaving</th><th>Exit Reason</th><th>Exit Type</th><th>Rehire Eligible</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->emp_id }}</td>
                        <td>{{ $r->full_name }}</td>
                        <td>{{ $r->date_of_joining }}</td>
                        <td>{{ $r->date_of_leaving }}</td>
                        <td>{{ $r->exit_reason ?? '—' }}</td>
                        <td>{{ $r->exit_type ?? '—' }}</td>
                        <td>
                            @if($r->rehire_eligible)<span class="pill pill-ok">Yes</span>@else<span class="pill pill-bad">No</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6 text-slate-500">No exited employees.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div>
@endsection
