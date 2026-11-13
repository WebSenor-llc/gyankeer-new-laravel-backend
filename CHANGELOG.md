# Changelog

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
