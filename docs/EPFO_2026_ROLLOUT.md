# EPFO 2026 — Production Rollout Runbook

Wage ceiling bump ₹15,000 → ₹25,000, cutover **01-Oct-2026**.

## Pre-deploy (staging first)

1. Back up DB — `mysqldump gyankeer > pre-epfo2026-$(date +%F).sql`
2. Review current `.env` — add if ops wants explicit overrides (defaults in `config/hreasy.php` already cover):
   ```
   HREASY_PF_WAGE_CAP=25000
   HREASY_EPS_WAGE_CAP=25000
   HREASY_EDLI_WAGE_CAP=25000
   HREASY_PF_CEILING_CUTOVER=2026-10
   HREASY_PF_ADMIN_FLOOR=500
   ```
3. For exempted-trust companies:
   ```sql
   UPDATE companies SET exempted_trust_flag=1 WHERE company_id IN (...);
   ```

## Deploy day (do NOT run before Oct 2026 payroll cutoff)

1. `php artisan down`
2. `git pull` on prod — branch merged to `main`
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan migrate --force` → adds `effective_from/to` + snapshot/topup/inspection columns
5. `php artisan db:seed --class=StatutorySlabSeeder --force` → inserts the two cutover rows
6. `php artisan hreasy:resync-formulas` → updates UI formula strings to show ₹25,000
7. `php artisan config:clear && php artisan cache:clear`
8. `php artisan up`

## Post-deploy sanity check (~5 min)

- `php artisan tinker` → confirm:
  - `StatutorySlab::rateAt('epf.wage_cap','2026-10')` returns `25000`
  - `StatutorySlab::rateAt('epf.wage_cap','2026-09')` returns `15000`
- Open ESS payslip view for one employee → sample shows ₹25,000 cap.
- Open `/payroll/settings` (or wherever `salary_components` renders) → EPF/EPS/EDLI formulas say `MIN(BASIC+DA,25000)`.

## First real run (Oct 2026 payroll)

- Pilot ONE company, pick an employee with basic ≥ ₹25k:
  ```
  php artisan payroll:recompute --year=2026 --month=10 --company=<id>
  ```
  Spot-check payslip: EE=3000, EPS=2083, ER=917, EDLI=125, `pf_wage_cap_snapshot=25000`.
- Then run full-company payroll.

## Do NOT

- Re-run `payroll:rebuild` / `payroll:recompute` on Apr–Sep 2026 periods casually. The command now prompts — answer **no** unless you intend to reprocess historicals (they'll still use ₹15k via the slab date range, but ECR regen may change downstream exports).
- Edit the `epf.wage_cap` slab rows after the first Oct 2026 run — the snapshot column protects already-generated slips, but new runs would silently shift.

## Rollback (if critical bug)

- `php artisan migrate:rollback --step=2` → drops new columns (payslips keep snapshot nullable, no data loss on existing slips).
- Delete the 25k slab row:
  ```sql
  DELETE FROM statutory_slabs WHERE `key`='epf.wage_cap' AND effective_from='2026-10-01';
  ```
- Revert code + redeploy.

## Operational heads-up

- **Employee take-home drops** for anyone whose basic sits in the ₹15k–₹25k band — Oct 2026 slip shows higher PF deduction and lower net pay. HR should circulate a one-pager; this is the single biggest employee-visible impact.
- **VPF opt-ins** at ₹15k must be manually revisited — **no auto-opt-in**. HR must collect fresh VPF declarations for affected employees before the Oct run.
- **Exempted trusts** — EPFO expects inspection charges on the ECR challan. Verify `salary_runs.pf_inspection_epf` / `pf_inspection_edli` match what the trust remits.
- **International Workers** — `employees.iw_flag` column was NOT added in this release (MySQL row-size limit on `employees` table). All employees are treated as non-IW. Introduce a sidecar table (`employee_statutory_meta`) if IW support is needed before the next ceiling event.
