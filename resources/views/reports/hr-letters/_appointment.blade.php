@include('reports.hr-letters._header')

<p>Dear {{ $e->first_name ?: $e->full_name }},</p>

<p>Further to the offer of employment dated earlier, we are pleased to confirm your appointment as
    <strong>{{ $e->designation->designation_name ?? '—' }}</strong>
    in the <strong>{{ $e->department->dept_name ?? '—' }}</strong> department
    of <strong>{{ $c->company_name ?? '' }}</strong>,
    effective from <strong>{{ $e->date_of_joining ? \Carbon\Carbon::parse($e->date_of_joining)->format('d M Y') : '_____________' }}</strong>.</p>

<p><strong>Compensation:</strong> Your total Cost to Company (CTC) is
    <strong>INR {{ number_format((float) ($e->current_ctc ?? 0), 2) }}</strong> per annum.
    Salary will be disbursed monthly after statutory deductions as per applicable law
    (EPF, ESI, Professional Tax, Labour Welfare Fund, TDS u/s 192).</p>

<p><strong>Probation:</strong> You will be on probation for a period of six (6) months from the date of joining,
    which may be extended at the company's discretion. Your services will be confirmed in writing upon
    successful completion of probation.</p>

<p><strong>Hours of Work:</strong> Standard working hours and shift assignment will be as per the company's
    attendance policy and the schedule applicable to your location.</p>

<p><strong>Confidentiality:</strong> You shall maintain strict confidentiality of all proprietary information,
    business processes, customer data and trade secrets, both during your employment and thereafter.</p>

<p><strong>Notice Period:</strong> Either party may terminate this employment by giving the notice period
    prescribed in the HR policy. The company reserves the right to terminate without notice in cases of
    misconduct, breach of policy or unauthorised absenteeism.</p>

<p><strong>Statutory Compliance:</strong> Your employment is subject to all applicable Indian labour laws
    including the Code on Social Security, 2020, Code on Wages, 2019 and POSH Act, 2013.</p>

<p>Kindly sign and return the duplicate copy of this letter as a token of your acceptance.</p>

@include('reports.hr-letters._footer')
