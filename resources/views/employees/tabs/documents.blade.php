@extends('layouts.app')

@section('title', 'Employee — Documents')

@section('content')
<div class="p-6">
    <div class="card p-8 max-w-3xl mx-auto text-center">
        <div class="text-6xl mb-4">🚧</div>
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Employee — Documents</h1>
        <p class="text-slate-500 mb-1">This page is scaffolded but not yet implemented.</p>
        <p class="text-xs text-slate-400 mb-4">View: <code>employees.tabs.documents</code></p>
        <a href="{{ route('dashboard') }}"
           class="inline-block mt-4 px-4 py-2 rounded-lg text-white font-semibold"
           style="background: var(--brand);">
           ← Back to Dashboard
        </a>
    </div>
</div>
@endsection
