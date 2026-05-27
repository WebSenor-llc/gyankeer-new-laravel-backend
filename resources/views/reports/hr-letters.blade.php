@extends('layouts.app')
@section('title', 'HR Letters')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">HR Letters</span></div>
    <h1 class="text-xl font-bold mb-3">HR Letters</h1>
    <p class="text-xs text-slate-500 mb-3">Download offer / appointment / confirmation / experience / relieving / NDA letters per employee as Word documents.</p>

    <form method="GET" class="mb-3 flex items-center gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or code…"
               class="border rounded px-2 py-1 text-sm w-64">
        <button type="submit" class="btn btn-primary text-xs">Search</button>
        @if(request('q'))
            <a href="{{ route('reports.hr-letters') }}" class="text-xs text-slate-500">Clear</a>
        @endif
    </form>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Date of Joining</th>
                    <th>Download Letters (Word)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td>{{ $e->emp_code ?: $e->emp_id }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td>{{ $e->designation->designation_name ?? '—' }}</td>
                        <td>{{ $e->date_of_joining }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($letterTypes as $slug => $label)
                                    <a href="{{ route('reports.hr-letters.download', ['empId' => $e->emp_id, 'type' => $slug]) }}"
                                       class="pill pill-info hover:bg-blue-100"
                                       title="Download {{ $label }} as Word file">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6 text-slate-500">No active employees yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())<div class="mt-3">{{ $employees->links() }}</div>@endif
</div>
@endsection
