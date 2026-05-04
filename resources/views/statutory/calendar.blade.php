@extends('layouts.app')
@section('title', 'Compliance Calendar')

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">Calendar</span></div>
    <h1 class="text-xl font-bold mb-1">Compliance Calendar</h1>
    <p class="text-xs text-slate-500 mb-4">Recurring statutory due dates for India payroll, finance and HR.</p>

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead><tr><th>Due Date</th><th>Task</th><th>Statute</th><th>Frequency</th><th>Owner</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($tasks as $t)
                    <tr>
                        <td class="font-semibold">{{ $t[0] }}</td>
                        <td>{{ $t[1] }}</td>
                        <td class="text-xs text-slate-500">{{ $t[2] }}</td>
                        <td>{{ $t[3] }}</td>
                        <td>{{ $t[4] }}</td>
                        <td>
                            @if($t[5] === 'ok')
                                <span class="pill pill-ok">On track</span>
                            @elseif($t[5] === 'warn')
                                <span class="pill pill-warn">Action soon</span>
                            @else
                                <span class="pill pill-bad">Overdue</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
