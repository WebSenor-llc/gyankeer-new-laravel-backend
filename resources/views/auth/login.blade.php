<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · Hreasy by WebSenor</title>
    <link rel="icon" href="/favicon.ico"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--brand:#B91C1C;--brand-dark:#7F1D1D;--ink:#0F172A;--line:#E2E8F0}
        body{font-family:'Inter',system-ui,sans-serif;background:#F8FAFC;color:var(--ink)}
        .grad-red{background:linear-gradient(135deg,#B91C1C,#7F1D1D)}
        .field{display:block;width:100%;border:1px solid var(--line);border-radius:8px;padding:10px 12px;font-size:14px;background:#fff}
        .field:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(185,28,28,.15)}
        .btn-primary{background:var(--brand);color:#fff;font-weight:600;padding:10px 14px;border-radius:8px;font-size:14px;width:100%;transition:background .15s}
        .btn-primary:hover{background:var(--brand-dark)}
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="grad-red text-white rounded-t-2xl px-8 py-6 shadow">
            <div class="font-extrabold tracking-wide text-2xl">Hreasy by WebSenor</div>
            <div class="text-xs opacity-80 mt-1">v2 · NEXTGEN — HR & Payroll Suite</div>
        </div>

        <div class="bg-white border border-t-0 border-[var(--line)] rounded-b-2xl px-8 py-8 shadow">
            <h1 class="text-lg font-semibold mb-1">Sign in to your account</h1>
            <p class="text-sm text-slate-500 mb-6">Use your work email and password.</p>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="login_email">Email</label>
                    <input id="login_email"
                           name="login_email"
                           type="email"
                           autocomplete="username"
                           required
                           autofocus
                           value="{{ old('login_email') }}"
                           class="field"
                           placeholder="you@websenor.com">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="password">Password</label>
                    <input id="password"
                           name="password"
                           type="password"
                           autocomplete="current-password"
                           required
                           class="field"
                           placeholder="••••••••">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600 select-none">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-[var(--brand)] focus:ring-[var(--brand)]">
                    Remember me on this device
                </label>

                <button type="submit" class="btn-primary">Sign in</button>
            </form>

            <p class="text-[11px] text-slate-400 mt-6 text-center">
                &copy; {{ date('Y') }} WebSenor. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
