@extends('layouts.app')
@section('title', 'POSH Compliance')

@section('content')
<div class="p-4 max-w-4xl">
    <div class="text-xs text-slate-500 mb-2">Statutory & Compliance / <span class="text-slate-900 font-semibold">POSH</span></div>
    <h1 class="text-xl font-bold mb-1">POSH Compliance (Sexual Harassment of Women at Workplace Act 2013)</h1>
    <p class="text-xs text-slate-500 mb-4">Tracks Internal Committee, training compliance, and §22 annual report obligations.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <div class="card p-4">
            <div class="text-[11px] text-slate-500 uppercase">Internal Committee</div>
            <div class="text-lg font-bold">Constituted</div>
            <div class="text-xs text-slate-500 mt-1">Mandatory: Presiding officer (woman), 2 internal, 1 external NGO member</div>
        </div>
        <div class="card p-4">
            <div class="text-[11px] text-slate-500 uppercase">POSH Training</div>
            <div class="text-lg font-bold">Quarterly</div>
            <div class="text-xs text-slate-500 mt-1">All employees must complete training annually</div>
        </div>
        <div class="card p-4" style="background:#FEF3C7;border-color:#FCD34D">
            <div class="text-[11px] text-amber-700 uppercase font-semibold">Next §22 Report Due</div>
            <div class="text-lg font-bold text-amber-700">31 January</div>
            <div class="text-xs text-slate-500 mt-1">Annual report to District Officer</div>
        </div>
    </div>

    <div class="card p-5">
        <h2 class="font-semibold mb-2">Compliance Requirements</h2>
        <ul class="text-sm space-y-2 text-slate-700">
            <li>✓ Internal Committee (IC) at every workplace with 10+ employees</li>
            <li>✓ Display of penal consequences and IC composition at conspicuous places</li>
            <li>✓ Annual training and orientation for IC members</li>
            <li>✓ Annual report under Section 22 to be filed by 31 January</li>
            <li>✓ Mandatory inclusion in Director's Report under Companies Act</li>
        </ul>
    </div>
</div>
@endsection
