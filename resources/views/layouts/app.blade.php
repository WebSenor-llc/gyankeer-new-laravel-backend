<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Hreasy by WebSenor</title>
    <link rel="icon" href="/favicon.ico"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--brand:#B91C1C;--brand-dark:#7F1D1D;--ink:#0F172A;--line:#E2E8F0}
        body{font-family:'Inter',system-ui,sans-serif;background:#F8FAFC;color:var(--ink)}
        .grad-red{background:linear-gradient(135deg,#B91C1C,#7F1D1D)}
        .pill{display:inline-flex;align-items:center;gap:.35rem;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600}
        .pill-ok{background:#DCFCE7;color:#166534}
        .pill-warn{background:#FEF3C7;color:#92400E}
        .pill-bad{background:#FEE2E2;color:#991B1B}
        .pill-info{background:#DBEAFE;color:#1E40AF}
        .card{background:#fff;border:1px solid var(--line);border-radius:12px}
        .tb-btn{display:inline-flex;align-items:center;gap:4px;padding:5px 9px;border:1px solid var(--line);background:#fff;border-radius:6px;font-size:12px;color:var(--ink);cursor:pointer}
        .tb-btn:hover{background:#FEF2F2;border-color:var(--brand);color:var(--brand)}
        .tb-btn.primary{background:var(--brand);color:#fff;border-color:var(--brand)}
        .grid-tbl{width:100%;border-collapse:separate;border-spacing:0;font-size:13px}
        .grid-tbl th{background:#F1F5F9;font-size:11.5px;text-transform:uppercase;letter-spacing:.04em;color:#475569;font-weight:600;padding:8px 10px;text-align:left;border-bottom:1px solid var(--line)}
        .grid-tbl td{padding:8px 10px;border-bottom:1px solid #F1F5F9;color:#1E293B}
        .sidebar a.active{background:#FEE2E2;color:var(--brand-dark);border-left:3px solid var(--brand);font-weight:600}
    </style>
</head>
<body>

<header class="grad-red text-white sticky top-0 z-40 shadow">
    <div class="flex items-center px-3 py-2 gap-2">
        <button onclick="document.getElementById('sidebar').classList.toggle('hidden')" class="p-2 hover:bg-white/10 rounded">☰</button>
        <a href="/" class="font-extrabold tracking-wide text-lg">Hreasy by WebSenor</a>
        <span class="text-[10px] font-semibold bg-white/25 px-1.5 py-0.5 rounded ml-1">v2 · NEXTGEN</span>

        <div class="ml-auto flex items-center gap-3">
            @auth
            <form method="POST" action="{{ route('switch-company') }}" class="inline" id="companySwitchForm">
                @csrf
                <select name="company_id" onchange="document.getElementById('companySwitchForm').submit()"
                        class="bg-white text-[var(--brand)] text-xs rounded px-2 py-1 font-semibold cursor-pointer"
                        title="All pages filter by this company until you change it">
                    @foreach(($allCompanies ?? collect()) as $c)
                        <option value="{{ $c->company_id }}" @selected(($activeCompanyId ?? 0) == $c->company_id)>
                            {{ $c->company_name }}
                        </option>
                    @endforeach
                </select>
            </form>
            @endauth
            <div class="flex items-center gap-2 bg-white/10 rounded-full pl-2 pr-3 py-1">
                <div class="w-7 h-7 rounded-full bg-white text-[var(--brand)] font-bold text-xs flex items-center justify-center">{{ substr(auth()->user()?->name ?? 'U', 0, 1) }}</div>
                <div class="text-xs">
                    <div class="font-semibold">{{ auth()->user()?->name ?? 'Guest' }}</div>
                    <div class="text-white/70">HR Admin</div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="ml-2">@csrf<button class="text-xs underline">Logout</button></form>
            </div>
        </div>
    </div>
</header>

<div class="flex" style="min-height:calc(100vh - 50px)">
    <aside id="sidebar" class="sidebar w-64 bg-white border-r border-[var(--line)] overflow-y-auto shrink-0" style="max-height:calc(100vh - 50px);position:sticky;top:50px">
        <nav class="py-2 text-[13px]">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50 {{ request()->is('/') ? 'active' : '' }}">📊 Home / Dashboard</a>
            <details open><summary class="px-4 py-2 hover:bg-slate-50 font-semibold cursor-pointer">HR — Master Config</summary>
                <div class="pl-9 text-slate-600">
                    <a href="{{ route('companies.index') }}"   class="block py-1.5 hover:text-[var(--brand)]">Manage Company</a>
                    <a href="{{ route('departments.index') }}" class="block py-1.5 hover:text-[var(--brand)]">Manage Departments</a>
                    <a href="{{ route('designations.index') }}"class="block py-1.5 hover:text-[var(--brand)]">Manage Designations</a>
                    <a href="{{ route('employees.index') }}"   class="block py-1.5 hover:text-[var(--brand)]">Manage Employee</a>
                    <a href="{{ route('exit-employees') }}"    class="block py-1.5 hover:text-[var(--brand)]">Exit Employees</a>
                </div>
            </details>
            <details open><summary class="px-4 py-2 hover:bg-slate-50 font-semibold cursor-pointer">Payroll Config</summary>
                <div class="pl-9 text-slate-600">
                    <a href="{{ route('manage-salary.index') }}"      class="block py-1.5 hover:text-[var(--brand)] font-semibold text-[var(--brand)]">⚙️ Manage Salary (per Employee)</a>
                    <a href="{{ route('salary-components.index') }}"  class="block py-1.5 hover:text-[var(--brand)]">Salary Components</a>
                    <a href="{{ route('salary-groups.index') }}"      class="block py-1.5 hover:text-[var(--brand)]">Manage Salary Groups</a>
                    <a href="{{ route('banks.index') }}"              class="block py-1.5 hover:text-[var(--brand)]">Manage Banks</a>
                    <a href="{{ route('payroll.generate') }}"         class="block py-1.5 hover:text-[var(--brand)] font-semibold text-[var(--brand)]">⚡ Salary Generation (by Group)</a>
                    <a href="{{ route('payroll.payslips.index') }}"   class="block py-1.5 hover:text-[var(--brand)]">📄 Payslips</a>
                    <a href="{{ route('payroll.runs.index') }}"       class="block py-1.5 hover:text-[var(--brand)]">Salary Runs (history)</a>
                    <a href="{{ route('reports.complete-salary') }}"  class="block py-1.5 hover:text-[var(--brand)]">Salary Simulation</a>
                    <a href="{{ route('incentives.index') }}"         class="block py-1.5 hover:text-[var(--brand)]">Incentives</a>
                    <a href="{{ route('arrears.index') }}"            class="block py-1.5 hover:text-[var(--brand)]">Arrears</a>
                    <a href="{{ route('deductions.listing') }}"       class="block py-1.5 hover:text-[var(--brand)]">Salary Deductions</a>
                    <a href="{{ route('payroll.transactions') }}"     class="block py-1.5 hover:text-[var(--brand)]">Salary Transactions</a>
                    <a href="{{ route('overtime-sheet') }}"           class="block py-1.5 hover:text-[var(--brand)]">⏱️ Overtime Sheet</a>
                </div>
            </details>
            <details><summary class="px-4 py-2 hover:bg-slate-50 font-semibold cursor-pointer">Statutory &amp; Compliance</summary>
                <div class="pl-9 text-slate-600">
                    <a href="{{ route('statutory.pf') }}"        class="block py-1.5 hover:text-[var(--brand)]">PF Challan / ECR</a>
                    <a href="{{ route('statutory.esi') }}"       class="block py-1.5 hover:text-[var(--brand)]">ESI Challan</a>
                    <a href="{{ route('statutory.pt') }}"        class="block py-1.5 hover:text-[var(--brand)]">Profession Tax</a>
                    <a href="{{ route('statutory.lwf') }}"       class="block py-1.5 hover:text-[var(--brand)]">LWF</a>
                    <a href="{{ route('statutory.tds') }}"       class="block py-1.5 hover:text-[var(--brand)]">TDS / Income Tax</a>
                    <a href="{{ route('statutory.form24q') }}"   class="block py-1.5 hover:text-[var(--brand)]">Form 24Q · Form 16</a>
                    <a href="{{ route('statutory.bonus') }}"     class="block py-1.5 hover:text-[var(--brand)]">Bonus Provision</a>
                    <a href="{{ route('statutory.gratuity') }}"  class="block py-1.5 hover:text-[var(--brand)]">Gratuity</a>
                    <a href="{{ route('statutory.posh') }}"      class="block py-1.5 hover:text-[var(--brand)]">POSH</a>
                    <a href="{{ route('statutory.calendar') }}"  class="block py-1.5 hover:text-[var(--brand)]">Compliance Calendar</a>
                </div>
            </details>
            <details><summary class="px-4 py-2 hover:bg-slate-50 font-semibold cursor-pointer">Attendance &amp; Leave</summary>
                <div class="pl-9 text-slate-600">
                    <a href="{{ route('attendance.daily') }}"         class="block py-1.5 hover:text-[var(--brand)]">Daily Attendance</a>
                    <a href="{{ route('attendance.counts') }}"        class="block py-1.5 hover:text-[var(--brand)] font-semibold text-[var(--brand)]">⚡ Quick Counts (SUGAM)</a>
                    <a href="{{ route('attendance.counts-workers') }}" class="block py-1.5 hover:text-[var(--brand)]">👷 Workers (by Contractor)</a>
                    <a href="{{ route('attendance.grid') }}"          class="block py-1.5 hover:text-[var(--brand)]">Bulk Attendance Grid</a>
                    <a href="{{ route('attendance.summary') }}"       class="block py-1.5 hover:text-[var(--brand)]">Summary Entry (P/W/L)</a>
                    <a href="{{ route('attendance.manual') }}"        class="block py-1.5 hover:text-[var(--brand)]">Manual Attendance</a>
                    <a href="{{ route('attendance.set-reporting') }}" class="block py-1.5 hover:text-[var(--brand)]">Set Reporting</a>
                    <a href="{{ route('attendance.view-reporting') }}"class="block py-1.5 hover:text-[var(--brand)]">View Reporting</a>
                    <a href="{{ route('leave.apply') }}"              class="block py-1.5 hover:text-[var(--brand)]">Leave Application</a>
                    <a href="{{ route('leave.online') }}"             class="block py-1.5 hover:text-[var(--brand)]">Online Leaves</a>
                    <a href="{{ route('attendance.tour') }}"          class="block py-1.5 hover:text-[var(--brand)]">Tour / ODs</a>
                    <a href="{{ route('leave.record') }}"             class="block py-1.5 hover:text-[var(--brand)]">Leave Record</a>
                    <a href="{{ route('leave.balance') }}"            class="block py-1.5 hover:text-[var(--brand)]">Leave Balance</a>
                    <a href="{{ route('attendance.upload') }}"        class="block py-1.5 hover:text-[var(--brand)]">Attendance Upload</a>
                </div>
            </details>
            <details><summary class="px-4 py-2 hover:bg-slate-50 font-semibold cursor-pointer">Reports</summary>
                <div class="pl-9 text-slate-600">
                    <a href="{{ route('reports.salary-sheet') }}"  class="block py-1.5 hover:text-[var(--brand)]">Salary Sheet</a>
                    <a href="{{ route('reports.salary-slip') }}"   class="block py-1.5 hover:text-[var(--brand)]">Salary Slip</a>
                    <a href="{{ route('reports.hr-letters') }}"    class="block py-1.5 hover:text-[var(--brand)]">HR Letters</a>
                    <a href="{{ route('reports.bank-sheet') }}"    class="block py-1.5 hover:text-[var(--brand)]">Bank Sheet</a>
                    <a href="{{ route('reports.increment') }}"     class="block py-1.5 hover:text-[var(--brand)]">Increment Report</a>
                    <a href="{{ route('reports.headcount') }}"     class="block py-1.5 hover:text-[var(--brand)]">Headcount Reports</a>
                    <a href="{{ route('reports.exit') }}"          class="block py-1.5 hover:text-[var(--brand)]">Exit Reports</a>
                </div>
            </details>
            <a href="{{ route('ess.index') }}" class="block px-4 py-2 hover:bg-slate-50">Self-Service (ESS)</a>
            <a href="{{ route('settings.index') }}" class="block px-4 py-2 hover:bg-slate-50 border-t border-[var(--line)] mt-2">Settings · RBAC</a>
        </nav>
    </aside>

    <main class="flex-1 p-5 overflow-x-hidden">
        @if(session('success'))
            <div class="mb-3 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-3 p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
