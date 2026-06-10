@extends('layouts.app')
@section('title', 'Edit Leave Balance')

@section('content')
<div class="p-4">
    @php $fyLabel = ($fy - 1) . '-' . substr((string)$fy, -2); @endphp

    <div class="text-xs text-slate-500 mb-2">
        Leaves / <a href="{{ route('leaves.balances', ['fy' => $fy]) }}" class="hover:underline">Leave Balances</a>
        / <span class="text-slate-900 font-semibold">Edit</span>
    </div>

    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-bold">Edit Leave Balance
                <span class="text-sm font-normal text-slate-500">— FY {{ $fyLabel }}</span>
            </h1>
            <div class="text-xs text-slate-500 mt-1">
                Emp <span class="font-mono">#{{ $empId }}</span> — <strong>{{ $empName }}</strong>
                @if($emp?->salary_group) &nbsp;•&nbsp; {{ $emp->salary_group->salary_group_name }} @endif
            </div>
        </div>
        <a href="{{ route('leaves.balances', ['fy' => $fy]) }}" class="tb-btn">← Back</a>
    </div>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="text-xs text-slate-500 mb-3">
        Closing = Opening + Accrued − Availed − Encashed − Lapsed (recomputed on save).
        Rows left all-zero with no existing balance are skipped.
        <strong>Note:</strong> the next payroll run recomputes Closing from the leave ledger — these edits override the current snapshot.
    </div>

    <form method="POST" action="{{ route('leaves.balances.update', $empId) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="fy" value="{{ $fy }}">

        <div class="card overflow-x-auto">
            <table class="grid-tbl">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Leave Type</th>
                        <th class="text-right">Opening</th>
                        <th class="text-right">Accrued</th>
                        <th class="text-right">Availed</th>
                        <th class="text-right">Encashed</th>
                        <th class="text-right">Lapsed</th>
                        <th class="text-right">Closing</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($types as $t)
                        @php $b = $existing->get($t->leave_code); @endphp
                        <tr>
                            <td class="font-mono text-xs">{{ $t->leave_code }}</td>
                            <td class="text-xs">{{ $t->leave_name }}</td>
                            @foreach(['opening' => 'opening_balance', 'accrued' => 'accrued_ytd', 'availed' => 'availed_ytd', 'encashed' => 'encashed_ytd', 'lapsed' => 'lapsed_ytd'] as $field => $col)
                                <td class="text-right">
                                    <input type="number" step="0.01" min="0"
                                           name="rows[{{ $t->leave_code }}][{{ $field }}]"
                                           value="{{ old("rows.{$t->leave_code}.{$field}", $b ? (float)($b->$col ?? 0) : '') }}"
                                           placeholder="0"
                                           class="border border-[var(--line)] rounded p-1 text-sm text-right w-20">
                                </td>
                            @endforeach
                            <td class="text-right font-bold text-slate-500">{{ $b ? rtrim(rtrim(number_format((float)($b->closing_balance ?? 0), 2, '.', ''), '0'), '.') ?: '0' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex gap-2">
            <button type="submit" class="tb-btn primary">Save Balances</button>
            <a href="{{ route('leaves.balances', ['fy' => $fy]) }}" class="tb-btn">Cancel</a>
        </div>
    </form>
</div>
@endsection
