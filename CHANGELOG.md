# Changelog

## 2026-11-14 — PF/FPF/ESI/VPF toggles + salary config gross fixes

- **FPF unchecked** now zeroes EPS; full employer 12% goes to EPF A/C 1 (EE = EPS + ER kept). Applied on payslip and PF ECR (EPS wage 0).
- **VPF** no longer deducted when PF is off (VPF needs EPF membership).
- **VPF field** on Manage Salary › Config now pre-fills and saves `employees.vpf_amount` (was ignored).
- **Gross drop on config save** fixed: Excel-imported employees whose `current_gross` exceeded visible components lost the unmapped amount on save (e.g. emp 13120 ₹27,581 → ₹24,547). Gap now shown in *Other Allow* with a note, so save keeps gross. Gap uses auto-calc values so Education isn't double-counted.
- **Salary structure** `special_allowance` now includes Education (structure has no education column; structure-driven runs dropped it).

## 2026-11-13 — Payslip leave balance shown as of slip month

- Payslip / bulk print Leave Balance (CL, PL) now = opening + accrued − `leave_ledger` consumed from FY start through slip month. Previously showed current closing balance, so old slips reflected later leaves (e.g. May slip showed PL 42 after June leave instead of 50).
- PL now shows 1 decimal like CL.

## 2026-10-20 — EPF/EPS/EDLI wage ceiling ₹15,000 → ₹25,000 (EPFO 2026)

Period-aware ceiling via `statutory_slabs` with `effective_from` / `effective_to` columns. Cutover **01-Oct-2026**: Apr–Sep 2026 runs stay at ₹15k; Oct 2026 onward at ₹25k.

- `config/hreasy.php`: fallbacks bumped; added exempted-trust inspection rates/floors, admin floor, cutover period.
- `app/Services/PFCalculator.php`: period-aware ceiling lookup, International Workers (`iw_flag`) uncapped path, prorated cap for mid-month joiners, `cap_snapshot` returned.
- `app/Services/PayrollEngine.php`: passes period + IW + worked days to `PFCalculator`; writes `payslips.pf_wage_cap_snapshot`; new `finaliseStatutoryTotals()` enforces ₹500/month admin floor and exempted-trust inspection charges (EPF 0.18% ≥ ₹8,750; EDLI 0.005% ≥ ₹1,250).
- New columns: `payslips.pf_wage_cap_snapshot`, `salary_runs.pf_admin_topup`, `salary_runs.pf_inspection_epf`, `salary_runs.pf_inspection_edli`, `companies.exempted_trust_flag`, `employees.iw_flag`.
- `statutory_slabs` + `StatutorySlab::rateAt(key, period)` for date-ranged lookups; existing `rate(key, fy)` kept for backward-compat.
- `StatutorySlabSeeder` seeds two `epf.wage_cap` rows (pre-cutover 15k, post-cutover 25k).
- `SalaryComponentSeeder` renders formulas from config at seed time.
- New `php artisan hreasy:resync-formulas [--period=YYYY-MM]` — rewrites display formulas on `salary_components` without touching compute logic.
- `payroll:recompute` / `payroll:rebuild` prompt before re-running pre-cutover periods.
- ESS payslip Blade now reads cap from config (sample) / `pf_wage_cap_snapshot` (real slips).
