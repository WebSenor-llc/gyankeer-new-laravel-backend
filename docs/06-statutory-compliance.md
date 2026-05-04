# 06 — Statutory Compliance

How Hreasy keeps your business legally compliant with Indian payroll regulations.

## What's covered

| Statute | Coverage in Hreasy |
|---|---|
| **EPF & MP Act 1952** | EPF, EPS, EDLI, Admin charges. ECR generation. |
| **ESI Act 1948** (replaced by CoSS 2020) | ESI per CoSS 2020 §2(88) wage definition. |
| **Code on Social Security 2020** | Wage definition with 50% add-back proviso (effective 21-Nov-2025). |
| **State Profession Tax Acts** | All 7 major states with PT (MH, KA, TN, WB, GJ, KL, TG). RJ/UP/HR/DL/CH have no PT. |
| **State Labour Welfare Fund Acts** | 6 states (MH, KA, TN, WB, GJ, PB) with correct frequency. |
| **Income Tax Act 1961 §192** | TDS with both Old and New regime, full FY 2025-26 slabs. |
| **Payment of Bonus Act 1965** | 8.33%–20% statutory bonus, ₹7k wage cap, ₹21k eligibility. |
| **Payment of Gratuity Act 1972** | 5+ year eligibility, formula, ₹20L tax-free cap. |
| **Sexual Harassment of Women at Workplace Act 2013** | POSH IC tracking, training, §22 annual report. |
| **Factories Act 1948** | Earned leave (Section 79). |
| **Shops & Establishment Acts (state)** | State-specific applicable. |
| **Digital Personal Data Protection Act 2023** | Aadhaar masking, encryption, retention period config. |

## EPF / Provident Fund

### What Hreasy does

For every payroll run, it:

1. Computes EPF wages = `min(Basic + DA + RA + add-back, ₹15,000)`
2. Calculates **Employee 12%**, **Employer 3.67%**, **EPS 8.33%**, **EDLI 0.5%**, **Admin 0.5%**
3. Updates `pf_ecr_records` table with per-member rows ready for ECR upload
4. Tracks NCP (Non-Contributing Period) days from attendance

### Compliance benefits

- Wage cap automatically applied (you can't accidentally over-contribute)
- ECR file ready for upload to EPFO unified portal
- UAN and EPF Member ID stored per employee for direct linkage
- Joiners flagged for Form 11 submission
- International workers supported (no wage cap for them)

### Filing deadlines

- **Challan + ECR upload:** by **15th of following month**
- **EPF returns (annual):** Form 3A, Form 6A — generated from system data

## ESI / Employee State Insurance

### CoSS 2020 §2(88) — the new rule

Effective **21-Nov-2025**, the Code on Social Security 2020 came into force,
replacing the ESI Act 1948 (and unifying definitions across EPF, ESI, Gratuity,
Bonus). Hreasy implements §2(88) in full:

```
ESI Wages = Basic + DA + Retaining Allowance
          + max(0, Excluded items − 50% × Total Remuneration)

Excluded items: HRA, Conveyance, Medical, Special, Bonus, OT, Commission, etc.

Eligibility: ESI Wages ≤ ₹21,000 (₹25,000 for PwD)
```

### Why this matters

Many employees who were **ineligible** under the pre-CoSS rule (because their
Gross > ₹21k) are now **eligible** because their Basic+DA is ≤ ₹21k.

Hreasy:
- Automatically extends coverage to newly-eligible employees
- Computes the 50% add-back proviso for high-allowance structures
- Exempts those whose CoSS wages exceed ₹21k after add-back

### The 50% proviso explained

If the **excluded items** (HRA + Conv + Bonus + etc.) exceed **half of total
remuneration**, the excess is added back to ESI wages.

| Setup | Add-back? | ESI Wages |
|---|---|---|
| Basic+DA 12k, HRA+etc 8k (40%) | No | 12,000 |
| Basic+DA 8k, HRA+etc 12k (60%) | Yes (₹2k) | 10,000 |
| Basic+DA 16k, HRA+etc 24k (60%) | Yes (₹4k) | 20,000 |
| Basic+DA 20k, HRA+etc 26k (57%) | Yes (₹3k) | 23,000 → exempt (>₹21k) |

This matches the worked examples in the **ESIC official letter** dated 16-Dec-2025.

### Compliance benefits

- Automatic application of 50% rule
- Per-IP wages and contributions captured for ESI challan
- Employees newly covered get statutory medical/sickness/maternity benefits
- Correct ₹25,000 wage cap for Persons with Disabilities

### Filing deadlines

- **ESI challan deposit:** by **15th of following month**
- **ESI return:** half-yearly (April-Sept by 11-Nov; Oct-Mar by 11-May)

## Profession Tax (state-wise)

### Coverage

Hreasy supports PT for:

| State | Slab pattern | Frequency |
|---|---|---|
| Maharashtra | ₹0 / ₹175 / ₹200 (₹300 in Feb) | Monthly |
| Karnataka | ₹0 / ₹200 | Monthly |
| Tamil Nadu | 6 tiers up to ₹208.50 | Half-yearly |
| West Bengal | ₹0 / ₹110 / ₹130 / ₹150 / ₹200 | Monthly |
| Gujarat | ₹0 / ₹200 | Monthly |
| Kerala | 9 tiers | Half-yearly |
| Telangana | ₹0 / ₹150 / ₹200 | Monthly |
| **No PT states** | Rajasthan, Delhi, Uttar Pradesh, Haryana, Chandigarh | — |

### Per-employee state assignment

Each employee has a `pt_state` field. The engine looks up the slab for that
state, applies the slab amount based on the employee's monthly Gross. If
the state is one without PT, it returns 0.

### Why this matters

If your workforce spans multiple states (Mumbai HQ + Bengaluru office +
Chennai factory), each employee gets the correct PT for their state. No
manual override per employee.

### Compliance

- Slabs updatable from `/settings` when a state revises them
- Per-employee per-period record stored in `pt_records` for filing
- February ₹300 special slab for Maharashtra handled automatically

## Labour Welfare Fund (state-wise)

### Coverage

| State | EE | ER | Frequency | Months |
|---|---|---|---|---|
| Maharashtra | ₹25 | ₹75 | Half-yearly | Jun, Dec |
| Karnataka | ₹20 | ₹40 | Annual | Dec |
| Tamil Nadu | ₹10 | ₹20 | Annual | Mar |
| West Bengal | ₹3 | ₹15 | Half-yearly | Jun, Dec |
| Gujarat | ₹6 | ₹12 | Half-yearly | Jun, Dec |
| Punjab | ₹5 | ₹20 | Monthly | every |
| **No LWF states** | Rajasthan, UP, Bihar, etc. | — |

The engine **only deducts in applicable months**. So Maharashtra LWF appears
in June and December payslips, not the other 10 months.

### Compliance

- Per-state, per-frequency lookup
- Employer contribution computed alongside employee
- LWF challan due dates per state schedule

## TDS / Income Tax §192

### Coverage

- **Both regimes** — Old (with 80C/D/E etc.) and New (§115BAC, simplified)
- **FY 2025-26 slabs** — fully implemented
- **Standard Deduction** — ₹50,000 (Old) / ₹75,000 (New)
- **§87A rebate** — ₹12,500 (Old, up to ₹5L taxable) / ₹60,000 (New, up to ₹12L taxable)
- **Surcharge** — tiered 10%/15%/25%/37% for high incomes
- **4% Health & Education Cess** on top
- **HRA exemption** under §10(13A)

### Per-employee declaration

Employees self-declare via ESS:

| Section | Old regime cap |
|---|---|
| 80C | ₹1,50,000 |
| 80D | ₹25,000 (₹50,000 senior) |
| 80CCD(1B) | ₹50,000 |
| 80E | unlimited |
| 80G | varies |
| 24B (home loan interest) | ₹2,00,000 |
| HRA exemption | varies |

### Forms generated

- **Form 16** — annual TDS certificate per employee, by 15-Jun
- **Form 24Q** — quarterly TDS return (Q1/Q2/Q3/Q4)
- **Form 12BB** — investment proof (employee submits via ESS)
- **Form 26AS** — pulled from TRACES (roadmap integration)

### Filing deadlines

| Item | Due |
|---|---|
| TDS deposit | 7th of next month |
| Form 24Q Q1 | 31-Jul |
| Form 24Q Q2 | 31-Oct |
| Form 24Q Q3 | 31-Jan |
| Form 24Q Q4 | 31-May |
| Form 16 issue | 15-Jun |

## Bonus (Payment of Bonus Act 1965)

### Coverage

```
Eligibility: Monthly Gross ≤ ₹21,000
Bonus Wage:  min(Basic + DA, ₹7,000)
Rate range:  8.33% (statutory min) to 20% (statutory max)
Annual Bonus = Bonus Wage × 12 × Rate%
Monthly Provision (accrual) = Annual Bonus ÷ 12
```

### Compliance

- Automatic eligibility check (Gross ≤ ₹21k)
- ₹7,000 wage cap applied (statutory ceiling per Act)
- Monthly provision tracked per employee for accounting accruals
- Annual disbursement (typically Diwali) tracked separately

## Gratuity (Payment of Gratuity Act 1972)

### Coverage

```
Eligibility: 5+ years of continuous service
Formula: (Last Basic + DA) × 15 × Years ÷ 26
Tax-free cap: ₹20,00,000 (§10(10))
Monthly provision: 4.81% × (Basic + DA)
```

### Compliance

- Years of service tracked from `date_of_joining`
- Monthly accrual at 4.81% (matches actuarial rate)
- On exit:
  - Eligibility check
  - Final amount calculation
  - Tax-free portion vs. taxable
  - Form L issuance for nominee

## POSH (Sexual Harassment of Women at Workplace Act 2013)

### What Hreasy tracks

- Per-employee POSH training completion (`posh_training_completed`)
- Training date and expiry
- Internal Committee composition (from designation flags)
- §22 annual report due-date alerts

### §22 Annual Report

Due by 31-Jan each year. Hreasy provides:

- Summary of complaints (from internal log)
- Training compliance %
- IC composition listing

## Compliance Calendar

```
Sidebar → Statutory & Compliance → Compliance Calendar
```

Pre-loaded due dates:

| Due | Task | Statute |
|---|---|---|
| 7th | TDS Payment | IT Act §200 |
| 15th | EPF / ECR Filing | EPF Act 1952 |
| 15th | ESIC Contribution | ESI Act / CoSS |
| 21st (MH) | Profession Tax | MH PT Act 1975 |
| 31-May | Form 24Q (Q4) | IT Act |
| 15-Jun | Form 16 Issue | IT Act §203 |
| 31-Jul | LWF (MH) | MH LWF Act |
| 30-Sep | Tax Audit (3CD) | IT Act §44AB |
| 31-Jan | POSH §22 Report | SHW Act 2013 |
| Annual | Bonus Disbursement | Bonus Act 1965 |
| Annual | Gratuity Provision | Gratuity Act 1972 |

Each task is colour-coded:
- 🟢 **On track** (regular monthly task)
- 🟡 **Action soon** (within next 30 days)
- 🔴 **Overdue**

## DPDP Act 2023 — Data Privacy

Hreasy is built with privacy-by-design:

| Requirement | How Hreasy handles |
|---|---|
| Encryption at rest | AES-256 for sensitive fields (Aadhaar, PAN, bank account) |
| Aadhaar masking | UI shows last 4 digits only by default |
| Consent management | Employees grant consent during onboarding (digital signature) |
| Data retention | Active: 7 years (IT Act); post-exit: 8 years (Gratuity audits) |
| Right to deletion | Documented process for post-retention deletion requests |
| Data localisation | All data stays on India-based servers (when self-hosted in India) |

## Audit-readiness

Hreasy generates a complete audit trail:

- Every salary change → `employee_career_events` row
- Every payroll run → `salary_runs` with timestamps for create/approve/post
- Every payslip → frozen once posted (no in-place edits)
- All filings (ECR, ESI challan, 24Q) traceable back to source payslips

Auditors get:
- Per-period bank file showing exactly who was paid what
- Per-employee year-end summary for Form 16 verification
- Statutory filing dates with challan/return references
- Override log if any manual adjustments were made
