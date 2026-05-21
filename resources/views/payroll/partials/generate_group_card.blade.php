@php
    $gid              = $group->salary_group_id;
    $groupHasExisting = $employees->isNotEmpty() && $employees->contains(fn($e) => $existingPayslipEmps->contains($e->emp_id));
@endphp
<div class="card mb-4" data-group-id="{{ $gid }}">
    <div class="px-4 py-2 border-b border-[var(--line)] bg-slate-50 flex flex-wrap items-center gap-2 rounded-t-lg">
        <span class="text-base">📂</span>
        <h2 class="text-sm font-bold">{{ $group->salary_group_name }}</h2>
        <span class="text-xs text-slate-500">· {{ $employees->count() }} employee(s)</span>

        @if($employees->isNotEmpty())
            <div class="ml-auto flex flex-wrap gap-2">
                <button type="button" onclick="exportSelected('csv', {{ $gid }})"
                        class="tb-btn primary" style="background:#16A34A;border-color:#15803D">⬇ CSV</button>
                <button type="button" onclick="exportSelected('xls', {{ $gid }})"
                        class="tb-btn primary" style="background:#0EA5E9;border-color:#0284C7">⬇ Excel</button>
                <button type="button" onclick="exportSelected('pdf', {{ $gid }})"
                        class="tb-btn primary" style="background:#DC2626;border-color:#B91C1C">⬇ PDF</button>
                @if($groupHasExisting)
                    <button type="button" onclick="openDeleteModal({{ $gid }})"
                            class="tb-btn primary" style="background:#7C2D12;border-color:#7C2D12;color:#fff">🗑️ Delete</button>
                @endif
            </div>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="grid-tbl text-xs">
            <thead>
                <tr style="background:#FEF2F2">
                    <th style="width:36px">
                        <input type="checkbox" id="grpCheckAll-{{ $gid }}" class="grpCheckAll" data-group-id="{{ $gid }}" onclick="toggleGroup(this, {{ $gid }})" checked>
                    </th>
                    <th>Emp ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Salary Group</th>
                    <th class="text-right">Gross (₹)</th>
                    <th>Bank A/C</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td>
                            <input type="checkbox" name="emp_ids[]" value="{{ $e->emp_id }}"
                                   class="empChk empChk-{{ $gid }}" data-group-id="{{ $gid }}" checked>
                        </td>
                        <td>{{ $e->emp_id }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td>{{ $e->department->dept_name ?? '—' }}</td>
                        <td>{{ $e->salary_group->salary_group_name ?? '—' }}</td>
                        <td class="text-right">{{ number_format((float) $e->current_gross, 0) }}</td>
                        <td class="text-xs text-slate-500">{{ $e->bank_account_no ?: '—' }}</td>
                        <td>
                            @if($existingPayslipEmps->contains($e->emp_id))
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-semibold">PAYSLIP EXISTS</span>
                                <a href="{{ route('payroll.payslips.show', [$e->emp_id, $year, $month]) }}"
                                   class="ml-1 text-[10px] text-[var(--brand)] underline font-semibold">View →</a>
                            @else
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">NEW</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-6 text-slate-500">No active employees in this group.</td></tr>
                @endforelse
            </tbody>
            @if($employees->isNotEmpty())
                <tfoot>
                    <tr style="background:#F1F5F9;font-weight:600">
                        <td></td>
                        <td colspan="4">Subtotal: <span class="grpCount-{{ $gid }}">{{ $employees->count() }}</span> / {{ $employees->count() }} employee(s)</td>
                        <td class="text-right">₹{{ number_format($employees->sum(fn($e) => (float) $e->current_gross), 0) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
