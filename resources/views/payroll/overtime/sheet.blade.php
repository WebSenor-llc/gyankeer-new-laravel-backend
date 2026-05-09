@extends('layouts.app')
@section('title', 'Overtime Sheet')
@section('content')
<div class="p-4">
    <div class="text-xs text-slate-500 mb-2">Payroll / <span class="text-slate-900 font-semibold">Overtime Sheet</span></div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold flex items-center gap-2"><span>⏱️</span> Overtime Sheet</h1>
        <div class="flex gap-2">
            <a href="{{ route('overtime-sheet', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               target="_blank" class="tb-btn primary" style="background:#DC2626;border-color:#B91C1C">⬇ Export PDF</a>
            <a href="{{ route('overtime-sheet.create', ['year'=>$year,'month'=>$month]) }}" class="tb-btn primary" style="background:#16A34A;border-color:#15803D">+ Add Overtime</a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    {{-- Filter form (matches SUGAM HR screenshot) --}}
    <form method="GET" class="card p-4 mb-3">
        <div class="grid grid-cols-12 gap-3 items-end">
            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Month &amp; Year :</label>
                <div class="flex gap-1">
                    <select name="month" class="border border-[var(--line)] rounded p-2 text-sm">
                        @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $n=>$lbl)
                            <option value="{{ $n }}" @selected($n == $month)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="year" value="{{ $year }}" class="border border-[var(--line)] rounded p-2 text-sm" style="width:90px">
                </div>
            </div>

            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Salary Group :</label>
                <select name="salary_group_id" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                    <option value="">-- All --</option>
                    @foreach($salaryGroups as $g)
                        <option value="{{ $g->salary_group_id }}" @selected(request('salary_group_id') == $g->salary_group_id)>{{ $g->salary_group_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Department :</label>
                <select name="dept_id" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                    <option value="">-- All --</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->dept_id }}" @selected(request('dept_id') == $d->dept_id)>{{ $d->dept_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Designation :</label>
                <select name="designation_id" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                    <option value="">-- All --</option>
                    @foreach($designations as $d)
                        <option value="{{ $d->designation_id }}" @selected(request('designation_id') == $d->designation_id)>{{ $d->designation_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Employee Type :</label>
                <select name="emp_type" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
                    <option value="">ALL</option>
                    <option value="ST" @selected(request('emp_type')=='ST')>Staff</option>
                    <option value="SB" @selected(request('emp_type')=='SB')>Sub-Staff</option>
                    <option value="WK" @selected(request('emp_type')=='WK')>Worker</option>
                </select>
            </div>

            <div class="col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Employee ID :</label>
                <input type="number" name="emp_id" value="{{ request('emp_id') }}" placeholder="e.g. 13139" class="block w-full border border-[var(--line)] rounded p-2 text-sm">
            </div>

            <div class="col-span-6 flex gap-2">
                <button type="submit" class="tb-btn primary" style="background:#16A34A;border-color:#15803D">View Report</button>
                <a href="{{ route('overtime-sheet') }}" class="tb-btn" style="background:#DC2626;color:#fff;border-color:#B91C1C">Cancel / Reset</a>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-4 gap-3 mb-4">
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">OT Entries</div><div class="text-lg font-bold">{{ $totals['count'] }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total OT Hours</div><div class="text-lg font-bold">{{ number_format($totals['hours'], 2) }}</div></div>
        <div class="card p-3"><div class="text-[11px] text-slate-500 uppercase">Total Amount</div><div class="text-lg font-bold">&#8377;{{ number_format($totals['amount'], 0) }}</div></div>
        <div class="card p-3" style="background:#F0FDF4;border-color:#86EFAC"><div class="text-[11px] text-green-700 uppercase font-semibold">Total Payable</div><div class="text-lg font-bold text-green-700">&#8377;{{ number_format($totals['payable'] ?? ($totals['amount'] - ($totals['esi'] ?? 0)), 0) }}</div></div>
    </div>

    {{-- Mirrors SUGAM HR's "Incentive (Manual)" listing --}}
    <div class="card overflow-x-auto">
        <h2 class="text-sm font-bold text-red-600 px-3 py-2 border-b border-[var(--line)]">₹ Incentive (Manual)</h2>
        <table class="grid-tbl text-xs">
            <thead style="background:#FEF2F2"><tr>
                <th>E. Code</th>
                <th>Name</th>
                <th>OT. Month</th>
                <th>OT. Year</th>
                <th class="text-right">OT. Hrs.</th>
                <th class="text-right">Rate (×)</th>
                <th class="text-right">Rate (₹/hr)</th>
                <th class="text-right">Amount</th>
                <th class="text-right">ESI</th>
                <th class="text-right">Payable</th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>{{ $r->emp_id }}</td>
                        <td>{{ $r->emp->full_name ?? '—' }}</td>
                        <td>{{ \DateTime::createFromFormat('!m', $r->period_month)->format('F') }}</td>
                        <td>{{ $r->period_year }}</td>
                        <td class="text-right">{{ rtrim(rtrim(number_format((float)$r->ot_hours, 2),'0'),'.') }}</td>
                        <td class="text-right">{{ rtrim(rtrim(number_format((float)$r->ot_rate, 2),'0'),'.') }}</td>
                        <td class="text-right">{{ number_format((float)($r->hourly_rate ?? 0), 2) }}</td>
                        <td class="text-right">{{ number_format((float)$r->ot_amount, 0) }}</td>
                        <td class="text-right text-slate-400">—</td>
                        <td class="text-right text-green-700"><strong>{{ number_format((float)$r->ot_amount, 0) }}</strong></td>
                        <td class="flex gap-1">
                            <a href="{{ route('overtime-sheet.create', ['emp_id'=>$r->emp_id,'year'=>$r->period_year,'month'=>$r->period_month]) }}" class="tb-btn" style="padding:2px 8px;font-size:11px">Edit</a>
                            <form method="POST" action="{{ route('overtime-sheet.destroy', $r->ot_id) }}" onsubmit="return confirm('Delete OT entry for {{ $r->emp->full_name ?? '' }}?')" class="inline">
                                @csrf
                                <button type="submit" class="tb-btn" style="padding:2px 8px;font-size:11px;background:#DC2626;color:#fff;border-color:#B91C1C">Del</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center py-6 text-slate-500">
                        No overtime entries for {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}.
                        <br><a href="{{ route('overtime-sheet.create', ['year'=>$year,'month'=>$month]) }}" class="text-[var(--brand)] font-semibold">+ Add the first OT →</a>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())<div class="mt-3">{{ $records->links() }}</div>@endif

    <p class="text-[11px] text-slate-500 mt-3">
        OT amount = Gross ÷ 30 ÷ 8 × Rate × Hours.
        When payroll is generated for this period, OT amounts here are added automatically to <strong>gross_earnings</strong> on each payslip.
    </p>
</div>
@endsection
