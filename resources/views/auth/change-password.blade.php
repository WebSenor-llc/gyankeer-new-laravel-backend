@extends('layouts.app')
@section('title', 'Change Password')

@section('content')
<div class="p-4 max-w-2xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">Account / <span class="text-slate-900 font-semibold">Change Password</span></div>
    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">Change Password</h1>
        <a href="{{ route('dashboard') }}" class="tb-btn">← Back to dashboard</a>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="card p-5 space-y-4" autocomplete="off">
        @csrf
        @method('PATCH')

        <div class="text-xs text-slate-500">
            Signed in as <strong class="text-slate-800">{{ auth()->user()?->login_email ?? auth()->user()?->name }}</strong>
            @if(auth()->user()?->password_last_changed)
                · last changed {{ auth()->user()->password_last_changed }}
            @endif
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="current_password">Current Password <span class="text-red-500">*</span></label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                   class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="password">New Password <span class="text-red-500">*</span></label>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                   class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]">
            <p class="text-[11px] text-slate-400 mt-1">Minimum 8 characters, with at least one letter and one number.</p>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="password_confirmation">Confirm New Password <span class="text-red-500">*</span></label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                   class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]">
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-[var(--line)]">
            <a href="{{ route('dashboard') }}" class="tb-btn">Cancel</a>
            <button type="submit" class="tb-btn primary">Update Password</button>
        </div>
    </form>
</div>
@endsection
