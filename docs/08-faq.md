# 08 — Frequently Asked Questions

## General

### What is Hreasy?

A complete HR & Payroll platform for Indian businesses. Handles employee
master data, attendance, leave, monthly payroll computation with full
statutory compliance (EPF, ESI, PT, LWF, TDS, Bonus, Gratuity), and
generates ready-to-upload bank disbursement files.

### Who built it?

WebSenor — an India-based engineering team with 10+ years of experience
implementing payroll for Indian factories, manufacturing units, and
multi-company groups.

### How is it different from Greytip / Keka / Zoho People?

- **CoSS 2020 §2(88) ready** out of the box — many competitors haven't
  implemented the new wage definition with 50% add-back yet
- **Multi-company in one license** — most competitors charge per company
- **State-aware out of the box** — PT for 7 states, LWF for 6 states
- **Edit statutory rates from UI** — no waiting for vendor patches when
  Govt revises rates
- **Built for factories / contract labour** — most SaaS HR is for office
  white-collar; Hreasy handles 16+ contractor groups, daily attendance,
  worker categories

### Is it cloud or on-premise?

Both. Cloud is the default; on-premise is available on the Enterprise plan.

### Can I migrate from my existing system?

Yes. We've migrated from:

- Excel + Tally Payroll
- SUGAM HR
- Greytip Saral PayPack
- Saral Pay Pack
- Spine HR
- Custom Java/Oracle systems

Contact us with your existing data sample (1-2 employees redacted), and we
quote migration based on shape and volume.

## Setup & onboarding

### How long does setup take?

| Setup tier | Time |
|---|---|
| Self-serve (you do it) | 1 day for 100 employees |
| Standard (we do master data) | 3-5 business days |
| Enterprise (we do data migration + training) | 2-4 weeks |

### Do you provide training?

Yes:

- **Self-serve:** documentation + video tutorials (free)
- **Standard:** 2-hour video call training session (free)
- **Enterprise:** 1-day onsite training (₹15,000 + travel)

### What employee data do you need?

Minimum:

- Employee ID, Full Name, DOJ
- PAN, Aadhar, UAN, EPF Member ID, ESI IP No
- Bank account, IFSC
- Salary breakdown (Basic, HRA, DA, Conv, Med, Spl)
- Department, Designation, Salary Group

We have a standard Excel template — fill it once, send to us, we import.

### Do you handle the EPFO/ESIC registration?

We don't act as a labour consultant — but we recommend partners who do.
Once you have your EPF Establishment Code and ESIC Code, the system handles
the rest.

## Payroll

### How accurate is the engine?

Tested against:
- 6 worked examples in the official ESIC notification (CoSS 2020) — exact match
- 10+ test cases for §192 TDS slabs (both regimes)
- 7 state PT slabs (verified against state department circulars)
- All 6 state LWF rates (verified against state Acts)
- Cross-verified with Greytip and Saral output for 50 sample employees

### Can it handle my multi-state workforce?

Yes. Each employee has a `pt_state` and `lwf_state` field. The engine looks
up the correct slab/rate for that employee's state. Office in Mumbai with
Bengaluru factory — both compute correctly in one payroll run.

### What about contractors?

Contract labour is a first-class concept in Hreasy:

- Contractors are stored as **Salary Groups** (e.g., "Contractor - Nathu Singh")
- Each contractor's labour is a set of employees in that group
- Per-contractor reports possible (count, gross, net, statutory)
- Bonus eligibility per worker (regardless of contractor)

### Does it generate bank file for non-NEFT modes?

Currently:
- ✅ NEFT bulk upload (HDFC, ICICI, SBI, Axis, Kotak, Yes formats)
- 🟡 RTGS — same CSV; bank handles routing based on amount
- 🟡 IMPS — same CSV
- 🟡 UPI bulk — roadmap

### Can I preview payroll before disbursing?

Yes — that's the **Salary Simulation** page (`/reports/complete-salary`). 32
columns showing every component for every employee. Sort, filter, export.
If something's wrong, recompute, re-review. Only when you click "Approve →
Generate Bank File" does the disbursement happen.

### What if I need to revise a single employee's payslip after running?

If the run is `Computed` or `Approved`:
1. Edit the employee's master data
2. Click "Recompute" on the run
3. Engine wipes and regenerates all payslips with new master data

If the run is `Posted` (locked for accounting):
- Create a separate adjustment run for that employee for the next period
- Or revert to Approved (Super Admin only) and re-post

### How are LOP days calculated?

```
LOP days = Absent days + (Half Day count × 0.5)

Where:
  Absent     = status 'Absent', 'LOP', or 'Unpaid Leave' in attendance_daily
  Half Day   = status 'Half Day'
  Paid leaves (PL/CL/SL) = status 'On Leave' → NOT LOP

Payable days = Total days in month − LOP days
PayRatio     = Payable / Total
Each component (Basic, HRA, etc.) × PayRatio
```

### I marked some leaves but engine still shows full salary?

Check if the leave was entered as `On Leave` (paid) — that does NOT cause
LOP. To deduct salary, mark them `Absent` instead.

## Compliance

### Will Hreasy keep up with Govt rate changes?

Yes:
- **Cloud customers**: rate updates pushed centrally on the same day
  Govt notification is issued
- **On-premise**: download a 1-line patch from our portal

You also have direct edit access at `/settings` to change rates yourself
without waiting.

### Is the system tested for ESIC audits?

Yes. The ECR file generated matches the ESIC unified portal's expected
format. Per-employee records (ESI IP No, Member Name, Wage, EE 0.75%, ER
3.25%, days worked) are stored in `esi_records` for audit trail.

### Can I produce an audit report for past payroll?

Yes:
- Per-period: `/reports/salary-sheet?year=Y&month=M` shows the full sheet
- Per-employee: `/employees/{id}/salary` history
- Per-statutory-head: `/statutory/pf-challan`, `/statutory/esi-challan`,
  `/statutory/pt`, etc.
- Yearly summary for tax filing: `/statutory/form24q?fy=2025-26`

### What about GDPR / DPDP Act 2023?

- Aadhaar masking in UI (last 4 digits)
- AES-256 encryption for sensitive fields at rest
- Data retention configurable per Act requirements
- Right-to-deletion process documented for post-retention requests
- Indian-region data hosting (cloud)

### Can I store data outside India?

For DPDP Act compliance, default deployment is in India. Contact us if you
need cross-border (some multinationals require US/EU mirror).

## Technical

### What if my company has 5,000+ employees?

Tested up to 10,000 employees per payroll run. Engine takes ~3 minutes for
10k. For 25k+, contact us — we tune the deployment for high-volume.

### Does it work on mobile?

The web UI is responsive and works on any phone browser. Native iOS/Android
apps are on the roadmap (Q3 2026).

### Can my employees access from outside the office?

Yes — it's a web app. With cloud hosting, employees access from anywhere
(or restrict to specific IPs/VPN if required).

### What's the uptime SLA?

- **Starter / Professional cloud:** 99.5% (about 4 hours/month max downtime)
- **Enterprise cloud:** 99.9% (about 45 minutes/month max)
- **On-premise:** depends on your infrastructure

### Backup policy?

- **Cloud:** automated daily backups, 30-day retention; on-demand snapshot before each payroll run
- **On-premise:** documented backup script provided; you run it on your schedule

### What if WebSenor goes out of business?

- Source code escrow available for Enterprise contracts
- Database export tools included — your data is yours
- We've been operating since 2018 with 200+ active customers

## Security

### How are passwords stored?

Bcrypt-hashed (industry standard). We never see your employee passwords.

### Is data encrypted in transit?

Yes — HTTPS with managed SSL certificates. TLS 1.2+ enforced.

### Can I integrate with my SSO?

Yes for Enterprise plan:
- Microsoft Entra ID / Azure AD
- Google Workspace SSO
- SAML 2.0 generic

### Do you do regular security audits?

Annual external penetration tests (results available on request for
Enterprise customers). OWASP Top 10 compliance documented.

### Where are logs stored?

Application logs: 90 days (cloud), configurable on-prem.
Audit logs (sensitive actions): 7 years (per IT Act).

## Customisation

### Can I add custom fields to the employee profile?

Yes — Enterprise plan. We add custom fields to your installation. Cost
varies by complexity (typically ₹5,000–₹20,000 for a one-time addition).

### Can I customise the payslip layout?

Yes. We provide template-based payslip generation. Custom designs at
₹5,000 per template. We accept Word/PDF/HTML reference designs.

### Can my company colours / logo appear?

Yes — even on Starter plan. Upload your logo and pick brand colour from
Settings.

### Can I add custom approval workflows?

Roadmap (Q4 2026). Currently the workflow is:
- Salary run: Draft → Computed → Approved → Posted (4 stages)
- Leave: Pending → Approved/Rejected (single approver)
- Custom multi-step approvals will come.

## Ending

### Can I export all my data and leave?

Yes. Use these:
- `/employees/export` — CSV of all employees
- `/payroll/transactions/export` — CSV of all GL entries
- `/reports/complete-salary` — per-period CSV
- Database snapshot via `mysqldump` on on-premise

We don't lock you in.

### Where can I see Hreasy in action?

- **Demo URL:** http://websenor.local:8080 (with sample data)
- **Live tour:** book a 30-minute demo at websenor.com/demo
- **Trial:** 30-day free Professional plan available
