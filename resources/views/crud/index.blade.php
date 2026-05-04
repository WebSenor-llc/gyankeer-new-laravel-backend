@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">HR / Master Config / <span class="text-slate-900 font-semibold">{{ $title }}</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">{{ $title }}</h1>
        <a href="{{ route($routeBase . '.create') }}" class="tb-btn primary">+ Add {{ $singular }}</a>
    </div>

    @if (session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if (!empty($searchable))
        <form method="GET" class="card p-3 mb-3 flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ $searchQuery }}"
                   placeholder="Search {{ implode(', ', array_map(fn($c) => str_replace('_',' ',$c), $searchable)) }}…"
                   class="border border-[var(--line)] rounded p-2 text-sm flex-1 min-w-60"/>
            <button type="submit" class="tb-btn primary">🔍 Search</button>
            @if($searchQuery)
                <a href="{{ route($routeBase . '.index') }}" class="tb-btn">Clear</a>
            @endif
        </form>
    @endif

    <div class="card overflow-x-auto">
        <table class="grid-tbl">
            <thead>
                <tr>
                    @foreach($listColumns as $col => $header)
                        <th>{{ $header }}</th>
                    @endforeach
                    <th style="width:140px;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        @foreach($listColumns as $col => $header)
                            <td>
                                @php $val = $rec->{$col}; @endphp
                                @if(is_bool($val) || in_array($col, ['active_flag','default_bank_flag','optional_flag','night_shift_flag','people_manager_flag','pf_applicable','esi_applicable','pt_applicable','lwf_applicable','gratuity_applicable','overtime_eligible','show_on_payslip','statutory_flag']))
                                    @if($val)
                                        <span class="pill pill-ok">Yes</span>
                                    @else
                                        <span class="pill pill-bad">No</span>
                                    @endif
                                @elseif(is_null($val) || $val === '')
                                    <span class="text-slate-400">—</span>
                                @else
                                    {{ $val }}
                                @endif
                            </td>
                        @endforeach
                        <td style="text-align:right">
                            <a href="{{ route($routeBase . '.edit', $rec->{$pkName}) }}" class="tb-btn">Edit</a>
                            <form action="{{ route($routeBase . '.destroy', $rec->{$pkName}) }}" method="POST" class="inline" onsubmit="return confirm('Delete this {{ strtolower($singular) }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="tb-btn" style="color:#991B1B;border-color:#FCA5A5">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($listColumns) + 1 }}" class="text-center py-6 text-slate-500">
                            No {{ strtolower($title) }} yet. <a href="{{ route($routeBase . '.create') }}" class="text-[var(--brand)] font-semibold">Add the first one →</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        <div class="mt-3">
            {{ $records->links() }}
        </div>
    @endif
</div>
@endsection
