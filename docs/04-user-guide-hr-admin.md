# 04 — User Guide for HR Admin

A practical guide for the person running HR & payroll day-to-day.

## Daily tasks

### Mark attendance

```
Sidebar → Attendance & Leave → Daily Attendance → Pick date
```

Two ways:

1. **Bulk** — click "+ Mark Attendance", "Mark all Present", override exceptions, Save
2. **Biometric sync** (if connected) — automatic from device, you only handle exceptions

### Approve leave applications

```
Sidebar → Attendance & Leave → Leave Approvals
```

- See pending applications
- Click ✓ Approve or ✗ Reject
- Approved leave deducts from balance and is treated as paid in payroll

### Onboard new joiners

```
Sidebar → Manage Employee → "+ Add New Employee"
```

After saving, the system:
- Creates a `users_roles` row for ESS login
- Auto-generates an offer/appointment letter (if configured)
- Adds to the active company's headcount

## Weekly tasks

### Verify employee data

Use Reports → Headcount and Reports → Salary Sheet (current period) to spot:

- Employees with NULL bank account (they won't get paid)
- Employees with NULL PAN (TDS will compute incorrectly)
- Employees with `tax_regime = 'Old'` but no investments declared

Edit each one at `/employees/{id}/edit` to fill in.

### Process resignations / exits

```
/employees/{id}/edit → Employment → Status = 'Exited' or 'Resigned'
```

Set:
- **Date of Leaving**
- **Last Working Day**
- **Exit Reason**
- **Rehire Eligible** flag

The next payroll will compute their F&F (final settlement, leave encashment)
when that feature is enabled. Until then, exited employees are excluded from
the regular monthly run.

## Monthly cycle

### Day 25-28 — Pre-payroll

| Task | Where |
|---|---|
| Verify all attendance marked for the month | `/attendance/daily` for each missing date |
| Approve all pending leaves | `/leave/online` |
| Process incentives / arrears for the month | `/incentives` and `/arrears` |
| Update loan EMIs / advance recoveries | `/loans` and `/payroll/salary-deductions/create` |
| Check for new joiners or exits | `/employees` and `/exit-employees` |

### Day 1 (next month) — Run payroll

| Task | Where |
|---|---|
| Create salary run | `/payroll/runs/create` |
| Run engine | Click "Run Payroll Engine" on run detail |
| Review totals | Run detail page + `/reports/complete-salary` |
| Identify outliers | Sort Salary Sheet by Net Pay; look for nil-pay or extreme cases |
| Fix issues | Edit employee → Recompute |
| Get HR approval | Click "Approve" |
| Generate bank file | "Generate Bank File" → download CSV |
| Upload to bank | HDFC/ICICI/SBI bulk-NEFT portal |

### Day 5-7 — Statutory filings

| Filing | Due | Where |
|---|---|---|
| TDS challan deposit | 7th of next month | `/statutory/tds` summary |
| EPF ECR + challan | 15th | `/statutory/pf-challan` → "Generate ECR" |
| ESI challan | 15th | `/statutory/esi-challan` |
| Profession Tax (state) | 21st in MH | `/statutory/pt` |

### Quarterly

- **Form 24Q** filing — `/statutory/form24q?fy=2025-26` → quarterly TDS return
- Provident Fund audit prep
- ESI inspector visit prep

### Annually

- **Form 16** issue — by 15-Jun for FY ending 31-Mar
- **Bonus disbursement** — typically Diwali, per Bonus Act 1965
- **Increment cycle** — `/employees/{id}/edit` → update salary; or bulk via Excel re-import
- **POSH §22 report** — by 31-Jan
- **Gratuity payouts** — for any exits with 5+ years service

## Common workflows

### Adding a salary increase for one employee

```
/employees/{id}/edit → Salary tab
  → Update current_basic, current_hra, current_da, current_gross
  → Save
```

The change takes effect from the next payroll run. If you've already run
this month's payroll, click "Recompute" on that run to apply the new salary.

The change is automatically logged in `employee_career_events` for audit.

### Bulk increment (e.g., 8% across-the-board)

For now this is a database-side operation — contact your WebSenor admin to
run a bulk update SQL. Roadmap: a UI page for "Apply % increment to selected
employees".

### Adding a one-off bonus to a specific employee for this month

```
/payroll/salary-deductions/create
  → Select employee
  → Type: NOT a deduction; set this aside for "Incentive" workflow instead

/incentives/create
  → Select employee, period, type, amount
  → Save
```

The next payroll computation includes the incentive in `incentive` column
on the payslip and adds to gross.

### Recovering an advance

```
/payroll/salary-deductions/create
  → Select employee
  → Type: Advance Recovery
  → Amount, period
  → Save
```

Adds to the payslip's `advance_recovery` column for that period only.

### Loan EMI auto-deduction

```
/loans/create  →  Set up the loan with EMI amount and start/end date
```

The engine should pick this up automatically every month while the loan is
active. (Currently this requires a small additional step — paste an entry
manually into `/payroll/salary-deductions/create` until full integration.)

### Marking some employees as POSH-trained

```
/employees/{id}/edit → Statutory tab → "POSH Training Completed" = Yes
                                       "POSH Training Date" = today
```

## Troubleshooting

### "Payroll engine timed out"

For 1000+ employees, the engine takes 30-60 seconds. If it times out:

1. Increase PHP execution time (`php.ini → max_execution_time = 300`)
2. Or run from terminal: `php artisan payroll:recompute --latest`

### "Some employees got nil net pay"

Likely because attendance shows them as fully Absent for the period.

1. Visit `/payroll/runs/{id}` and look at the per-payslip table
2. Sort by Net Pay — find the zeros
3. Check `/attendance/daily` for those employees on each day
4. Mark them Present where they should have been
5. Recompute the run

### "Net pay is more than gross"

Should never happen. If it does, check:

- Are statutory deductions all 0? (PT/LWF state code wrong?)
- Visit `/employees/{id}` → Statutory tab — is `pt_state` and `lwf_state`
  set correctly for this employee?

### "Bank file rejected by bank portal"

Common causes:

- Account number has spaces/special chars (must be digits only)
- IFSC has lowercase letters or extra spaces
- Beneficiary name has special characters (& or apostrophe)

Edit affected employees, fix the data, regenerate the bank file.

## Productivity tips

| Tip | How |
|---|---|
| Switch companies fast | Use header dropdown — no need to log out |
| Search any employee in < 2 sec | `/employees?q=name-fragment` |
| See total payroll cost in one number | Open Salary Simulation, scroll to footer total CTC |
| Check who's on leave today | Dashboard → On Leave KPI card |
| Email payslip to employee | (when integration enabled) tick employee on `/reports/salary-slip` and click "Email" |
| Lock a payroll period (no further edits) | Click "Post to GL" on the run — moves to `Posted` (read-only) |

## Permissions

You're typically given an **HR Admin** role. This grants access to:

- All employee data within active company
- All payroll modules
- Reports
- Limited settings (statutory rate edits — depending on your tier)

You CANNOT (without Super Admin):
- Add/remove companies
- Change global statutory rates (only view)
- Delete payroll runs (only via DB)
- Add new user accounts to the system

For those tasks, request via Super Admin.
