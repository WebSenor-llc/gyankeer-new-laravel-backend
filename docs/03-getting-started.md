# 03 — Getting Started

A 30-minute guide to set up Hreasy and run your first payroll.

## Step 1 — Sign in (1 minute)

Open your Hreasy URL (provided by WebSenor) and sign in with the admin
credentials shared during onboarding.

```
Default demo:
  URL:      http://websenor.local:8080
  Email:    admin@websenor.com
  Password: password
```

After login you land on the **Dashboard** — currently empty until you've
set up companies and employees.

## Step 2 — Set up your company (3 minutes)

Top-right of every page there's a **Company dropdown**. On a fresh setup,
you'll see only one default company — let's add yours.

1. Sidebar → **HR — Master Config → Manage Company → "+ Add Company"**
2. Fill in:
   - **Company Code** (short code like `GTPPL`)
   - **Company Name** (full legal name)
   - **CIN, PAN, GSTIN** (statutory IDs)
   - **EPF Establishment Code, ESIC Code**
   - **Registered address**
   - **FY Start Month** (typically 4 for April)
3. **Save**

Repeat for each legal entity. The dropdown switches between them.

## Step 3 — Add your departments and designations (3 minutes)

1. Sidebar → **Manage Departments** → "+ Add Department"
   - Operations, HR, Finance, Production, Security, etc.
2. Sidebar → **Manage Designations** → "+ Add Designation"
   - Mention min/max gross per role for sanity checks during salary entry.

## Step 4 — Set up salary groups (3 minutes)

A **Salary Group** is a category of employees with the same statutory
applicability (PF, ESI, PT, LWF, Bonus, Gratuity).

Common groups:

| Group | Type | PF | ESI | Bonus | Notes |
|---|---|---|---|---|---|
| Office Staff | Staff | ✓ | ✓ (if eligible) | ✓ | Senior/management roles |
| Sub-Staff | Sub-Staff | ✓ | ✓ | ✓ | Mid-level operations |
| Worker | Worker | ✓ | ✓ | ✓ | Factory floor |
| Contract Labour | Contract | ✓ | ✓ | — | Through contractor |
| Intern / Trainee | Trainee | — | — | — | No statutory deductions |

1. Sidebar → **Manage Salary Groups → "+ Add Salary Group"**
2. Set name, type, applicability flags
3. **Save**

## Step 5 — Add banks (1 minute)

1. Sidebar → **Manage Banks → "+ Add Bank"**
2. Enter bank name, IFSC, branch (you only need one bank to start)

## Step 6 — Add your first employee (5 minutes)

1. Sidebar → **Manage Employee → "+ Add New Employee"**
2. Fill in the 7 sections:
   - **Profile** — name, DOB, gender, contact
   - **Employment** — company, dept, designation, salary group, DOJ
   - **Salary** — Basic, HRA, DA, etc.
   - **Bank** — account, IFSC
   - **Statutory** — PAN, Aadhar, UAN, EPF Member ID, ESI IP No, PT/LWF state
   - **Address** — mailing + permanent
   - **Emergency Contact**
3. **Save**

You'll land on the new employee's profile.

### Bulk import alternative

If you have an Excel of existing employees:

1. Use the **ExportSheet template** (we provide it on signup)
2. Fill in all employees
3. Email it to your WebSenor onboarding contact — we run a one-time bulk
   import and confirm the count

For 50–500 employees this is much faster than manual entry.

## Step 7 — Verify statutory rates (2 minutes)

1. Sidebar → **Settings → Statutory Rates**
2. Confirm:
   - **EPF wage cap** ₹15,000
   - **EPF rates** Employee 12%, Employer 3.67%, EPS 8.33%, EDLI 0.5%, Admin 0.5%
   - **ESI rates** 0.75% / 3.25%, wage cap ₹21,000
   - **TDS slabs FY 2025-26** New + Old regime
   - **PT slabs** for your state
   - **LWF rates** for your state

These are pre-seeded with FY 2025-26 figures. Edit only if your business
has special exemptions or the Govt has revised them.

## Step 8 — Run your first payroll (5 minutes)

### A. Mark attendance for the month

1. Sidebar → **Attendance & Leave → Daily Attendance**
2. Pick the date, click "+ Mark Attendance"
3. For most employees who showed up: bulk-action **"Mark all Present"**
4. Override exceptions: mark a few as **Absent** (LOP) or **On Leave** (paid)
5. **Save Attendance**

Repeat for each working day of the month — or skip for now and mark only
exceptions (engine treats unmarked days as Present).

### B. Create the salary run

1. Sidebar → **Payroll Config → Salary Generation**
2. Click **"+ New Salary Run"**
3. Pick:
   - **Company** (active company auto-selected)
   - **Year** (e.g., 2026)
   - **Month** (e.g., 4 for April)
4. **Create Run**

Lands on the run detail page in `Draft` status with 0 payslips.

### C. Run the payroll engine

1. Click **"Run Payroll Engine"** in the action bar
2. Confirm the prompt
3. Wait ~10 seconds for 500 employees
4. Page reloads with status banner: *"Payroll computed: 472 of 472 payslips
   generated."*

### D. Review

1. The run detail page now shows totals filled in:
   - Eligible Employees, Total Earnings, Total Deductions, Total Net Payout
   - Statutory totals card (EPF, ESI, PT, LWF, TDS, etc.)
2. Below: a table of the first 50 payslips

### E. Verify samples

- Open `/employees/{id}/salary` for a worker — should show the prorated salary
- Open `/reports/complete-salary?year=2026&month=4` — see all 472 in one table

### F. Approve

If totals look right:

1. Click **"Approve"** → status moves to `Approved`
2. Click **"Post to GL"** (optional, for accounting integration)

### G. Generate bank file

1. Click **"Generate Bank File"**
2. CSV downloads instantly — open in Excel, verify totals match
3. Upload to your bank's bulk-NEFT portal (HDFC, ICICI, SBI, Axis all accept this format)

## Step 9 — Review reports

Sidebar → **Reports**:

| Report | When to use |
|---|---|
| **Salary Sheet** | Pre-disbursement review with finance team |
| **Bank Sheet** | Same data as Bank File but readable on screen |
| **Salary Simulation** (Payroll Config) | Hand to leadership — 32 columns showing every formula |
| **PF Challan** (Statutory) | Filing EPF return |
| **ESI Challan** | Filing ESI return |

## Step 10 — Set up self-service for employees

1. Share the Hreasy URL with each employee
2. Their login email = the **company_email** field on their profile
3. They reset password via "Forgot Password" link
4. They log in to:
   - View payslips
   - Submit IT declaration (Old/New regime + investments)
   - Apply for leave
   - Download Form 16

Done. You've completed your first payroll cycle.

## Recurring monthly cycle (the simple version)

Once set up, every subsequent month is just:

```
Day 1-30:  HR marks attendance daily (via UI or biometric sync)
Day 27-31: Process leave applications
Day 1 (next):
   1. Create salary run for last month
   2. Click "Run Payroll Engine"
   3. Review on Salary Simulation page
   4. Approve
   5. Generate Bank File → upload to bank
   6. Bank credits employees → mark Disbursed
   7. File EPF ECR (use the PF Challan page export)
   8. File ESI challan
   9. Issue payslips (auto-emailed if configured)
```

End-to-end: **45 minutes** for 500 employees.

## What's next

- **[04-user-guide-hr-admin.md](04-user-guide-hr-admin.md)** — operational details for HR managers
- **[05-user-guide-employee.md](05-user-guide-employee.md)** — what your employees can do themselves
- **[06-statutory-compliance.md](06-statutory-compliance.md)** — how Hreasy keeps you compliant
