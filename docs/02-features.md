# 02 — Features

A complete catalogue of what Hreasy does, organised the way users see it.

## A. Master Data Management

| Feature | What it gives you |
|---|---|
| **Multi-Company** | Manage 1–50+ legal entities under one login. Each with its own CIN, PAN, GSTIN, EPF/ESIC code, registered address. |
| **Department Hierarchy** | Department + sub-department, headcount budgets, attrition targets, GL cost centre mapping. |
| **Designation Master** | Grade + level + band, salary range (min/max gross), people-manager flag, contract-vs-permanent flag. |
| **Salary Groups** | Group-wise PF/ESI/PT/LWF/Gratuity applicability. Different rules for staff vs sub-staff vs worker vs contractor. |
| **Bank Master** | Pre-define all banks with IFSC, MICR, NEFT/RTGS/IMPS file formats so payslip-to-bank handoff is one click. |
| **Salary Components** | Earning / Deduction / Reimbursement / Statutory / Employer-Contribution. Each with calc type (fixed / percentage / formula / slab), tax treatment, IT exemption section. |
| **Holiday Master** | National + state-wise + optional + restricted holidays per FY. |
| **Shift Master** | Shift code, in/out time, break, weekly off pattern, OT eligibility, location restriction, gender restriction. |

## B. Employee Lifecycle

### Hire to Retire

| Stage | What Hreasy does |
|---|---|
| **Pre-onboard** | Collect personal + statutory + bank info via configurable form |
| **Onboard** | Auto-generate offer/appointment/confirmation letter with company letterhead |
| **9-Tab Profile** | Profile · Education · Employment · Statutory · Bank · Documents · Family · Career · Salary |
| **Salary History** | Versioned record of every salary revision with old/new gross, hike %, effective date |
| **Promotions / Transfers** | Track via career events; old vs new designation, dept, salary auto-captured |
| **Confirmation** | Probation tracking, auto-generated confirmation letter on date_of_confirmation |
| **Exit / F&F** | Resignation date, last working day, notice period, exit reason, rehire-eligible flag |

### Bulk Operations

- **Bulk Excel import** — paste 500 employees at once with the standard ExportSheet template
- **Bulk Edit** — change reporting manager, salary group, or PT state for multiple employees in one shot
- **Bulk Marking** — mark attendance, approve leave, send appraisal letters to selected employees

## C. Attendance & Leave

| Feature | Detail |
|---|---|
| **Daily Attendance** | Mark Present / Absent / On Duty / Half Day / Weekly Off / Holiday per employee per date |
| **Bulk Marking** | "Mark all 472 as Present" then override the few exceptions |
| **Search & Filter** | Find any employee in the daily list by name / dept / shift |
| **Manual Entry + Biometric Sync** | Both supported (biometric requires connector setup) |
| **Leave Application** | Employee submits via ESS, manager approves; auto-deducts from balance |
| **Leave Balance** | Per-employee × leave-type with opening, earned, used, balance |
| **Leave Types** | EL / CL / SL / Compensatory Off + custom, configurable annual entitlement, accrual rule, encashment cap |
| **POSH Compliance** | Annual training tracking, IC member roster, §22 report due-date alerts |

## D. Payroll Engine

This is the core of Hreasy. Every employee, every component, every statutory rule.

### What it computes

For each employee, every month, the engine produces:

```
EARNINGS
  Basic, HRA, DA, Conveyance, Medical, Special Allowance
  Bonus (provision), Arrears, Overtime, Reimbursement, Leave Encashment
  → Gross Earnings

DEDUCTIONS (employee-paid)
  EPF (12% of capped wages)
  ESI (0.75% of CoSS 2020 wages)
  Profession Tax (state slab)
  LWF (state + frequency)
  TDS (§192 with full slabs, 80C/80D, §87A rebate, surcharge, cess)
  Loan EMI / Advance Recovery / Fines
  → Net Pay

EMPLOYER COST (CTC components)
  Employer EPF (3.67%) + EPS (8.33%) + EDLI (0.5%) + Admin (0.5%)
  Employer ESI (3.25%)
  Gratuity provision (4.81%)
  Bonus provision (8.33% × min(Basic+DA, ₹7k))
  → Total Employer Cost
```

### Key engine capabilities

| Feature | Detail |
|---|---|
| **CoSS 2020 §2(88) wages** | Implements the new "wages" definition with the 50% add-back proviso. Compliant with ESIC notification dated 21-Nov-2025. |
| **Attendance proration** | Reads daily attendance; LOP days reduce gross proportionally. Half days = 0.5 LOP. Paid leave (PL/CL) does NOT reduce gross. |
| **State-aware PT** | Maharashtra (₹200, ₹300 in Feb), Karnataka, Tamil Nadu, Bengal, Gujarat, Kerala, Telangana — and ZERO for states without PT (Rajasthan, UP, Delhi, Haryana). |
| **State-aware LWF** | Half-yearly (Jun/Dec for MH, WB, GJ), annual (Dec for KA, TN), monthly (PB), or none (RJ, UP). |
| **TDS for both regimes** | Old + New (§115BAC). Full slabs for FY 2025-26. §80C up to ₹1.5L, §80D mediclaim, §80CCD(1B) NPS extra ₹50k, §80E education loan, §24B home loan. §87A rebate. Surcharge for >₹50L. |
| **HRA exemption** | Under §10(13A): minimum of (HRA received, Rent − 10% of Basic+DA, 50%/40% of Basic+DA for metro/non-metro). |
| **Idempotent re-runs** | "Recompute" wipes prior payslips and regenerates. Safe to run multiple times. |
| **Per-employee skip handling** | If an employee has missing data, engine skips and reports them in the status banner — doesn't fail the whole run. |

### Approval workflow

```
DRAFT  →  COMPUTED  →  APPROVED  →  POSTED to GL
        (engine)     (HR)         (Finance)
```

## E. Statutory & Compliance (India)

| Module | Coverage |
|---|---|
| **EPF / ECR** | Auto-generate ECR file from posted payroll. Per-member UAN, Member ID, EE 12% / EPS 8.33% / ER 3.67% / EDLI 0.5% / Admin 0.5%. NCP days. Filed status tracking. |
| **ESI Challan** | Per-IP wages, EE 0.75%, ER 3.25%, days worked. CoSS 2020 wage base. |
| **Profession Tax** | State-wise slab application; export per-employee details for filing. |
| **LWF** | State + frequency-aware; only deducted in applicable months. |
| **TDS / Form 24Q** | Quarterly return generation per quarter with deductee PAN, gross paid, TDS deducted, tax paid. |
| **Form 16** | Annual TDS certificate per employee with FY filter. |
| **Bonus** | Statutory Bonus Act 1965 — eligibility (≤₹21k), wage cap (₹7k), 8.33%–20% rate. |
| **Gratuity** | Per Payment of Gratuity Act 1972. Eligibility 5+ years, formula (Basic+DA) × 15 × Years ÷ 26. Tax-free up to ₹20L. Monthly accrual at 4.81%. |
| **POSH** | Internal Committee tracking, training compliance, §22 annual report deadline. |
| **Compliance Calendar** | Pre-loaded with statutory due dates: TDS 7th, EPF 15th, ESI 15th, PT 21st (MH), Form 24Q quarterly, Form 16 by 15-Jun, etc. |

## F. Reports

| Report | What it shows |
|---|---|
| **Salary Sheet** | Period-wise, every employee, every component — full breakdown for HR/Finance review |
| **Salary Slip** | Single payslip viewer with Indian-format payslip (employer header, employee block, earnings/deductions tables, net pay in words) |
| **Bank Sheet** | Disbursement file — employee, account, IFSC, amount — ready for bank upload |
| **HR Letters** | Offer / Appointment / Confirmation / Experience / Relieving / NDA letter generator |
| **Increment Report** | Last increment date, %, old gross, new gross per employee |
| **Headcount Report** | Active employee count by department × employee type |
| **Exit Report** | Resigned/exited employees with exit reason, rehire eligibility |
| **Statistical Report** | Aggregate KPIs — employees, payslips, runs, transactions |
| **Salary Simulation** | 32-column complete view with formula-annotated headers and column totals |

## G. Self-Service Portal (ESS)

What every employee can do on their own — without HR ticketing:

- View latest payslip + 12 months of history
- Download payslip PDF
- Submit IT declaration (Old/New regime, 80C/80D/80CCD/80E/24B, HRA rent)
- View Form 16 (downloadable PDF when configured)
- Apply for leave; see status of pending applications
- View leave balance per type
- Update personal contact details
- View career history, salary revisions, increments

## H. Multi-Tenancy & Permissions

| Capability | Detail |
|---|---|
| **Active Company Switcher** | Header dropdown — instantly filters all data |
| **Per-Company Master Data** | Departments, salary groups, payroll runs scope by company |
| **Shared Master Data** | Banks, salary components, designations, holidays, statutory rates are global |
| **Role-Based Access** | Roles: Super Admin, HR Admin, Payroll Admin, Manager, Employee. Each with curated access. (Future: configurable per-permission RBAC.) |
| **Data Privacy** | DPDP Act 2023 compliant — Aadhaar masking, PII encryption, retention period configuration. |
| **Audit Logs** | All sensitive actions (salary changes, payroll posting, employee creation) tracked with user + timestamp. |

## I. Settings & Configuration

| Setting | Editable from |
|---|---|
| EPF wage cap (₹15,000) | `/settings` |
| ESI wage cap (₹21,000 / ₹25,000 PwD) | `/settings` |
| EPF / EPS / EDLI rates | `/settings` |
| ESI rates (employee 0.75%, employer 3.25%) | `/settings` |
| State PT slabs | `/settings` |
| State LWF rates | `/settings` |
| TDS slabs (Old + New regime, FY-wise) | `/settings` |
| Standard deduction (₹50k Old / ₹75k New) | `/settings` |
| §87A rebate amounts | `/settings` |
| Surcharge thresholds | `/settings` |
| Bonus rates / wage caps | `/settings` |
| Gratuity rate (4.81%) | `/settings` |
| Cess rate (4%) | `/settings` |

When the Govt of India revises any rate via Finance Bill, your admin updates it on this page — no support call, no software upgrade.

## J. Integrations (available / on roadmap)

| Integration | Status |
|---|---|
| Bank bulk-NEFT (HDFC / ICICI / SBI / Axis / Kotak / Yes) | ✅ CSV export ready |
| TRACES (Form 16 Part A pull) | 🟡 Roadmap |
| EPFO (UAN / KYC / ECR upload) | 🟡 Roadmap |
| ESIC portal (challan upload) | 🟡 Roadmap |
| Biometric attendance (eSSL, Matrix, Realtime) | 🟡 Connector available |
| Tally / SAP / Oracle Financials | 🟡 GL JSON export ready |
| Slack / Teams / WhatsApp (payslip delivery) | 🟡 Roadmap |
| HR information API (REST) | 🟡 Roadmap |
