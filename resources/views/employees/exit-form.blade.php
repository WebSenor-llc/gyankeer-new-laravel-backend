@extends('layouts.app')
@section('title', 'Mark Employee as Exited')
@section('content')
<div class="p-4 max-w-3xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        HR /
        <a href="{{ route('exit-employees') }}" class="hover:underline">Exit Employees</a> /
        <span class="text-slate-900 font-semibold">{{ $emp->full_name }}</span>
    </div>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="px-4 py-3 border-b border-[var(--line)] bg-slate-50 rounded-t-lg flex items-center gap-2">
            <span class="text-lg">⚠️</span>
            <h1 class="text-lg font-bold">Mark Employee as Exited</h1>
        </div>

        <div class="p-4 grid grid-cols-2 gap-4 text-sm border-b border-[var(--line)]">
            <div><span class="text-xs text-slate-500 uppercase">Emp ID</span><br><strong>{{ $emp->emp_id }}</strong></div>
            <div><span class="text-xs text-slate-500 uppercase">Name</span><br><strong>{{ $emp->full_name }}</strong></div>
            <div><span class="text-xs text-slate-500 uppercase">Department</span><br>{{ $emp->department->dept_name ?? '—' }}</div>
            <div><span class="text-xs text-slate-500 uppercase">Designation</span><br>{{ $emp->designation->designation_name ?? '—' }}</div>
            <div><span class="text-xs text-slate-500 uppercase">Date of Joining</span><br>{{ $emp->date_of_joining ?? '—' }}</div>
            <div><span class="text-xs text-slate-500 uppercase">Current Status</span><br>
                <span class="text-xs px-1.5 py-0.5 rounded
                    @if($emp->employment_status === 'Active') bg-green-100 text-green-800
                    @else bg-red-100 text-red-800 @endif">{{ $emp->employment_status }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('exit-employees.store', $emp->emp_id) }}" class="p-5 space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Last Working Day *</label>
                    <input type="date" name="date_of_relieving" required
                           value="{{ old('date_of_relieving', $emp->date_of_relieving ?? now()->toDateString()) }}"
                           class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                    <p class="text-[11px] text-slate-500 mt-1">Payroll will pro-rate up to this date for the resignation month.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Reason *</label>
                    <select name="exit_reason" required class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm">
                        <option value="">— Select reason —</option>
                        @foreach(['Resigned','Terminated','Retired','Absconded','Death','Contract End'] as $r)
                            <option value="{{ $r }}" @selected(old('exit_reason', $emp->exit_reason) === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notice_served_flag" value="1" @checked(old('notice_served_flag', $emp->notice_served_flag))>
                    <span>Notice period served as per contract</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Exit Notes / Handover Summary</label>
                <textarea name="exit_notes" rows="4"
                          class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm"
                          placeholder="Reason explanation, handover items, FNF clearance status, etc.">{{ old('exit_notes', $emp->exit_notes) }}</textarea>
            </div>

            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900">
                <strong>What happens on save:</strong>
                <ul class="list-disc list-inside mt-1">
                    <li><code>employment_status</code> set to "{{ '{Resigned|Exited}' }}" based on reason</li>
                    <li><code>active_flag</code> set to false — payroll engine will skip them in future runs</li>
                    <li>Their <code>date_of_relieving</code> is recorded — the engine pro-rates the FINAL month's salary up to this date</li>
                    <li>They appear on the Exit Employees list. You can re-activate them later if it was a mistake</li>
                </ul>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-[var(--line)]">
                <a href="{{ route('exit-employees') }}" class="tb-btn">Cancel</a>
                <button type="submit" class="tb-btn primary" style="background:#DC2626;border-color:#B91C1C">
                    💾 Save &amp; Mark as Exited
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
