# 05 — User Guide for Employees

> Welcome to your Hreasy Self-Service portal. This guide walks you through what you can do on your own — without raising a ticket to HR.

## Logging in

1. Open the Hreasy URL shared by your HR (e.g., `https://hr.yourcompany.com`)
2. Email = your **company email** as registered in HR
3. First-time? Click "Forgot Password" → check your email for a reset link
4. Set a strong password

## Your dashboard (`/ess`)

After login you see:

```
┌──────────────────────────────────────────────────────┐
│  YOUR NAME                                           │
│  Designation · Department          Emp ID: 13002      │
└──────────────────────────────────────────────────────┘

┌──────────────┬──────────────┬──────────────┐
│ 💰 Latest     │ 📊 IT         │ 📄 Form 16    │
│ Payslip       │ Declaration   │               │
│ Apr 2026      │ Tax: New      │ Annual TDS    │
│ Net ₹25,018   │ regime        │ Certificate   │
└──────────────┴──────────────┴──────────────┘

┌─ Leave Balance ─────────────────────────────┐
│ Earned Leave (EL): 12 days                  │
│ Casual Leave (CL): 4 days                   │
│ Sick Leave (SL):   6 days                   │
└─────────────────────────────────────────────┘

┌─ Recent Leave Applications ─────────────────┐
│ 5-Apr to 6-Apr    CL    2 days    Approved   │
│ 12-Mar             SL    1 day     Approved   │
└─────────────────────────────────────────────┘
```

## Viewing your payslip

```
Sidebar → Self-Service (ESS) → My Payslips
```

You'll see all your past payslips. Click any month to see the full breakdown:

- **Earnings:** Basic, HRA, DA, Conveyance, Medical, Special, Bonus
- **Deductions:** EPF, ESI, Professional Tax, LWF, TDS, any loan EMI / fines
- **Net Pay** in your bank account
- **Pay date, mode (NEFT), reference number**

Click the print icon to download a PDF for your records.

## Submitting your IT declaration

This is critical — your TDS for the year is calculated from what you declare here.

```
Sidebar → Self-Service → IT Declaration
```

### Step 1 — Choose Tax Regime

- **New Regime** (default for FY 2025-26) — lower rates, no exemptions for 80C/HRA/etc., higher standard deduction (₹75,000)
- **Old Regime** — higher rates but lets you claim 80C, 80D, HRA, home loan interest, etc.

Most people earning < ₹15L find **New Regime** is better. If you have:
- ₹1.5L 80C investments + ₹50k 80D mediclaim + ₹2L home loan interest + significant HRA exemption

…then Old Regime might save you more. Compare both before choosing.

### Step 2 — Declare Investments (Old Regime only)

| Field | What | Max |
|---|---|---|
| **80C** | EPF, PPF, ELSS, LIC, NSC, tuition fees, principal repayment of home loan | ₹1,50,000 |
| **80D** | Mediclaim premium for self/spouse/kids/parents | ₹25,000 (₹50,000 for senior) |
| **80CCD(1B)** | NPS additional contribution | ₹50,000 |
| **80E** | Education loan interest | unlimited |
| **80G** | Donations to approved charities | varies |
| **24B** | Home loan interest on self-occupied property | ₹2,00,000 |
| **HRA Rent Paid** | Annual rent paid (only if you live in rented accommodation and HRA is part of your salary) | varies |

### Step 3 — Save

Your declaration takes effect from the next payroll. TDS will be recalculated
using your declared deductions.

> 🔔 **Important:** Submit by 30-Apr or 31-May each FY. After that, HR can't
> apply mid-year changes to TDS.

## Applying for leave

```
Sidebar → Self-Service → Apply for Leave
```

1. Pick **Leave Type** (EL / CL / SL)
2. Pick **From Date** and **To Date**
3. Enter **Reason**
4. **Submit**

Your application goes to your manager for approval. Track status under **Recent Leave Applications**.

### Checking your leave balance

```
/ess  →  "Leave Balance" card
```

Shows for each leave type:
- **Earned** for the year
- **Used** so far
- **Balance** remaining

If you see balance is 0 for a type, you'll need a paid leave under another type, or take it as LOP (Loss of Pay — will reduce salary).

### Cancelling a leave application

If your manager hasn't approved yet, you can withdraw it from the same page. After approval, contact HR to cancel.

## Form 16 (Annual TDS Certificate)

```
Sidebar → Self-Service → Form 16
```

- Pick the **Financial Year** (e.g., 2025-26)
- View summary: Annual gross, standard deduction, taxable income, regime
- Download PDF (when laravel-dompdf is installed by your admin)

This is your proof of TDS for filing your **Income Tax Return**. You'll need it before 31-Jul each year.

## Updating your information

Click your name top-right → **My Profile** (or visit `/employees/{your-id}/edit`).

You can update:

- Personal mobile, personal email
- Mailing / permanent address
- Emergency contact name/relation/phone
- Marital status, blood group

You CANNOT update:

- Date of birth, joining date (HR only)
- PAN, Aadhar, UAN, EPF/ESI numbers (HR only)
- Salary, designation, department (HR only)
- Bank account (raise a ticket — needs verification)

## Common questions

### "Why did my net pay drop this month?"

Possible reasons:

1. **Increased TDS** — your annual income crossed a slab; TDS adjusts
2. **Loan EMI started** — check Deductions section for "Loan EMI" line
3. **LOP days** — were you absent without leave? Check `payable_days` on payslip — if < 30, that's the reason
4. **Investment declaration changed regime** — if you switched from Old to New, your TDS recalculates

### "Why was no ESI deducted earlier but now there is?"

Code on Social Security 2020 (effective 21-Nov-2025) redefined wages. If
your **Basic + DA** is ≤ ₹21,000, you're now ESI-eligible — even if your
total Gross was above ₹21,000 before.

This is good news — you now get ESIC medical benefits, sickness, maternity,
disability cover.

### "Why is my colleague's PT ₹200 but mine is zero?"

Profession Tax depends on your work state, not your home state. Rajasthan,
UP, Delhi, Haryana have **no PT**. Maharashtra, Karnataka, West Bengal,
Tamil Nadu do.

If you think it's wrong, ask HR to update your `pt_state`.

### "I forgot my password"

Click "Forgot Password" on the login page. A reset link goes to your
**company email**.

### "I never received the password reset email"

Check spam. If still nothing, your company email may be invalid in HR records — ask HR to update your `company_email` field, then try again.

### "I want to download all my payslips for tax filing"

Currently single-month download only. Ask your HR to use the bulk export
feature (or wait — bulk download is coming in next release).

## Privacy

Hreasy is compliant with the **Digital Personal Data Protection Act 2023**:

- Your data is encrypted at rest (AES-256)
- Aadhaar masking is enabled — even HR sees only last 4 digits
- Active employees: data retained per IT Act (7 years)
- Post-exit: 8 years (per Payment of Gratuity, EPF audit needs)

You can request deletion of your personal data after the retention period via
written request to your HR (mandatory under DPDP Act).

## Mobile access

Hreasy is fully responsive. On your phone:

- Open the same URL in any browser (Chrome, Safari, Firefox)
- Log in
- All pages adapt to mobile screen

A native iOS/Android app is on the roadmap.

## Need help?

- Issue with payslip → contact your HR
- Login issue → "Forgot Password" or contact IT/HR
- Data error in your profile → request via your HR
- Suggestion → tell your HR; they can pass it to WebSenor support
