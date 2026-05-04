@extends('layouts.app')
@section('title', 'HR Letters')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Reports / <span class="text-slate-900 font-semibold">HR Letters</span></div>
    <h1 class="text-xl font-bold mb-3">HR Letters</h1>
    <p class="text-xs text-slate-500 mb-3">Generate offer / appointment / confirmation / experience / relieving / NDA letters per employee.</p>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Emp ID</th><th>Name</th><th>Designation</th><th>Date of Joining</th><th>Letters</th></tr></thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td>{{ $e->emp_id }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td>{{ $e->designation->designation_name ?? '—' }}</td>
                        <td>{{ $e->date_of_joining }}</td>
                        <td>
                            <span class="pill pill-info">Offer</span>
                            <span class="pill pill-info">Appointment</span>
                            <span class="pill pill-info">Confirmation</span>
                            <span class="pill pill-info">Experience</span>
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
