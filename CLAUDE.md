# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**Hreasy by WebSenor** — Laravel 13 (PHP 8.3+) HR & Payroll backend for Indian businesses (factories, multi-entity groups). Implements EPF, ESI, PT, LWF, TDS §192, Bonus, Gratuity, POSH, and **Code on Social Security 2020 §2(88)** wage rules. Multi-company; web admin UI + Sanctum API for mobile/ESS.

Stack: Laravel 13, MySQL (DB `heasy`), Blade + Tailwind v4 / Vite v8, Sanctum, session+queue+cache on `database` driver.

## Commands

```bash
composer setup            # install + .env + key:generate + migrate + npm build
composer dev              # concurrent: artisan serve + queue:listen + pail + vite
composer test             # config:clear then artisan test (PHPUnit)
php artisan test --filter=FooTest          # single test
php artisan migrate --seed
php artisan db:seed --class=SalaryUpdateV2Seeder
```

Custom payroll commands (see `app/Console/Commands/`):

```bash
php artisan payroll:rebuild --year=2026 --month=4 --company=1 [--force]
   # full pipeline: truncates payslips/salary_runs/statutory tables,
   # re-runs SalaryUpdateV2Seeder + SetEmployeeStateRJSeeder, recomputes via PayrollEngine
php artisan payroll:recompute {runId|--latest|--year=Y --month=M --company=C}
   # wipes payslips for the run and re-runs engine with current calculator logic
php artisan payroll:reset                  # ResetPayrollData
php artisan payroll:wipe-group             # WipeGroupPayroll (per salary_group)
php artisan esi:export-comparison          # ExportEsiComparison
```

`payroll:rebuild` is destructive — confirm `--company` and period before running unless `--force` is intended.

## Architecture

### Multi-tenant by company (session-scoped)

`app/Http/Middleware/SetActiveCompany.php` runs on every web request:
- Reads `session('active_company_id')`, falls back to first active `Company`.
- Binds singleton `app('active_company_id')` and shares `$activeCompanyId` + `$allCompanies` with all views (header dropdown).
- `POST /switch-company` updates the session key.

Controllers and the `CrudController` base class read this via `session('active_company_id')` to scope queries. API routes do NOT use this middleware — pass `company_id` explicitly.

### PayrollEngine + per-statute calculators

`app/Services/PayrollEngine.php` is the orchestrator. Constructor-injects seven calculators (one per statute):

```
PFCalculator  ESICalculator  PTCalculator  LWFCalculator
TaxCalculator  GratuityCalculator  BonusCalculator
```

`PayrollEngine::run($companyId,$year,$month)` creates a `SalaryRun`, iterates active employees with `cursor()`, calls `computeForEmployee()` per row (also entry point used by `payroll:rebuild`/`payroll:recompute`), and aggregates totals back onto the `SalaryRun`.

All statutory rates, wage caps, state-wise PT/LWF slabs, TDS slabs (Old + New FY 2025-26), surcharge, 87A rebate, bonus cap, gratuity, DPDP retention live in **`config/hreasy.php`** — override via `HREASY_*` env vars. CoSS 2020 §2(88) "50% add-back proviso" is implemented in `ESICalculator`; do not duplicate the rule elsewhere.

Statutory output tables (recreated each `payroll:rebuild`): `payslips`, `salary_runs`, `salary_transactions`, `pf_ecr_records`, `esi_records`, `pt_records`, `lwf_records`, `tds_records`, `form24q_records`, `bonus_provisions`, `gratuity_register`.

### CrudController pattern

`app/Http/Controllers/CrudController.php` is an abstract base for master-data modules (Companies/Departments/Designations/Banks/Holidays/SalaryComponents/etc.). Subclasses set `$modelClass`, `$routeBase`, `$listColumns`, `$searchable`, optional `$companyScope` (FK column to filter by active company), and implement `fields()`. Shared Blade views: `resources/views/crud/{index,form}.blade.php`. Prefer extending this over hand-rolling another CRUD controller.

### Models — non-conventional primary keys

Most models override `$primaryKey` (e.g. `Employee.emp_id`, `Company.company_id`). All use `$guarded = []` with explicit `$casts`. `Employee` uses `SoftDeletes`. Be careful with route-model binding — current routes use `{empId}` and resolve manually.

### Routes

- `routes/web.php` — full admin UI: dashboard, employees (9 tabs: education/employment/statutory/bank/documents/family/career/salary), payroll (generate/runs/payslips/transactions), statutory (PF/ESI/PT/LWF/TDS/Form24Q/Form16/bonus/gratuity/POSH/calendar), attendance & leave, shifts/holidays, reports, ESS, settings. All under `auth` middleware.
- `routes/api.php` — Sanctum-guarded mobile/ESS endpoints under `App\Http\Controllers\Api\*` (employees / attendance punch+GPS / leave / payslips / ESS `/me`) plus public biometric+GPS webhooks. Note: the `Api\` controller namespace is referenced by routes but the directory is not present in `app/Http/Controllers/` — these endpoints are not wired and will error until implemented.

### Tests

`tests/Feature` and `tests/Unit` currently only contain `ExampleTest.php`. PHPUnit (not Pest) is the runner; `composer test` runs `config:clear` first.

## Conventions

- Money fields are `decimal` casts; never use floats for currency math.
- All India-specific rates and caps come from `config/hreasy.php`. Update there, not in calculator code.
- New statutory output tables should be truncatable by `payroll:rebuild` — add the table name to `$payrollOutputTables` in `RebuildPayroll.php`.
- Web controllers should respect `app('active_company_id')` for any company-scoped read/write.
- Use `Carbon` for dates; FY start month is configurable via `HREASY_FY_START_MONTH` (default 4 = April).
