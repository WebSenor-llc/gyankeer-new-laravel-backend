# 01 — Product Overview

## The problem we solve

Indian payroll is hard:

- **5+ statutes change every Finance Bill** — EPF, ESI, PT (per state), LWF, TDS slabs, bonus caps. Most software needs a code update every year.
- **Code on Social Security 2020 (effective 21-Nov-2025)** redefined "wages" — the calculation base for ESI, EPF, Gratuity. Many existing payroll systems are still using the old definition.
- **Multi-state complexity** — Profession Tax differs by state (Maharashtra, Karnataka, Tamil Nadu, West Bengal all have different slabs); Labour Welfare Fund frequency varies (half-yearly in some, annual in others).
- **Worker / contractor diversity** — Factories run a mix of permanent staff, contract labour from multiple contractors, daily-wagers — each on different salary structures, ESI applicability, and PF coverage.
- **Manual = error-prone** — payroll errors trigger ESI/PF audits, employee disputes, and bank reversal mess.

## What Hreasy delivers

A single platform where one HR person (or even just a finance executive) can run end-to-end monthly payroll for thousands of employees in **under 10 minutes**, with full statutory compliance baked in.

### Key benefits at a glance

| Benefit | What it means |
|---|---|
| **CoSS 2020 ready** | Engine implements the new wage definition with the 50% add-back proviso. Your ESI/PF computations match the Govt's letter dated 21-Nov-2025 line by line. |
| **Multi-company in one login** | Switch between sister concerns from a header dropdown. All data filters automatically. |
| **State-aware compliance** | PT, LWF, regional holidays, all factor in employee state. |
| **One-click payroll** | "Run Payroll Engine" button reads attendance + salary master + statutory rates → produces payslips for every employee. |
| **Bank file in seconds** | One click downloads a CSV in the format HDFC/ICICI/SBI/Axis bulk-NEFT portals accept. |
| **Editable rates** | When the Govt changes EPF cap or TDS slabs, your admin updates them from the Settings page — no developer needed. |
| **Audit trail** | Every salary change, every increment, every approval is logged in the career-events table. |
| **Self-service** | Employees see their own payslips, declare investments, download Form 16 — no HR ticketing. |

## Why we built it

The team at WebSenor has implemented payroll for 200+ Indian businesses. We saw the same patterns over and over:

1. Companies pay ₹50,000–₹2,00,000/year for legacy on-premise payroll software
2. Every March they hire a consultant for FY-changeover (₹15k–₹40k)
3. Every quarter the EPFO/ESIC files have manual fixes
4. ESI add-back rule (CoSS 2020) was missed by most software for the first year
5. Labour audits found inaccurate state PT being deducted (e.g., Maharashtra slabs applied to Rajasthan employees)

Hreasy is our answer: a modern, cloud-native, India-first payroll platform that handles all of this out of the box.

## Key differentiators

| Hreasy | Typical legacy payroll software |
|---|---|
| CoSS 2020 §2(88) wages with 50% add-back | Most still on pre-CoSS Gross-based ESI |
| Multi-company built-in | One license per company; per-extra-company fee |
| Cloud-deployable, also runs on-premise | Usually on-premise only, requires Windows server |
| Statutory rates editable from UI | Need vendor support call for every Finance Bill change |
| 9-tab employee profile + bulk Excel import | Manual entry one-by-one |
| Real-time payroll preview before disbursement | Compute → discover errors → recompute |
| Works on any device (responsive web) | Windows desktop only |

## Sample customer profile

> **Company:** Gyankeer Tobacco Products Pvt. Ltd.
> **Industry:** Tobacco / FMCG manufacturing, Rajasthan
> **Employees:** 472 across 7 sister concerns
> **Mix:** 40 office staff + 432 contract labour across 16 contractor groups
> **Compliance:** EPF, ESI under CoSS 2020 (no PT/LWF in RJ)
> **Before Hreasy:** 5 days/month for payroll on Excel + Tally Payroll
> **With Hreasy:** Payroll closed in 2 hours. Bank file generated in 1 click.

## Target market sizing

| Segment | Employee range | Why Hreasy fits |
|---|---|---|
| Small Manufacturing | 50–500 | Replaces ₹5,000/mo Excel-and-spreadsheet workflow |
| Mid-Size Factories | 500–2,000 | Replaces ₹50k–₹2L/year legacy on-premise software |
| Multi-Entity Groups | 2,000–5,000 | One platform vs. 5 separate licences |
| Contract Labour-Heavy Ops | Any size | The contractor-group model is uniquely supported |

## What's next in this documentation

- **[02-features.md](02-features.md)** — every module spelled out
- **[03-getting-started.md](03-getting-started.md)** — your first 30 minutes with Hreasy
