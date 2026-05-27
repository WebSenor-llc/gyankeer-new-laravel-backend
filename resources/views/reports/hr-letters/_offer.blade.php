@include('reports.hr-letters._header')

<p>Dear {{ $e->first_name ?: $e->full_name }},</p>

<p>With reference to your application and the subsequent interview you had with us, we are pleased to offer you the position of
    <strong>{{ $e->designation->designation_name ?? 'the designation discussed' }}</strong>
    in our organisation, on the terms and conditions discussed.</p>

<p>Your proposed date of joining shall be on or before
    <strong>{{ $e->date_of_joining ? \Carbon\Carbon::parse($e->date_of_joining)->format('d M Y') : '_____________' }}</strong>.
    Your total Cost to Company (CTC) is
    <strong>INR {{ number_format((float) ($e->current_ctc ?? 0), 2) }}</strong> per annum,
    inclusive of all statutory and non-statutory components as per the detailed annexure to be issued at the time of joining.</p>

<p>This offer is subject to:</p>
<ul>
    <li>Successful background verification.</li>
    <li>Submission of all educational and previous employment proofs.</li>
    <li>Medical fitness as per our policy.</li>
    <li>Acceptance of the company's Code of Conduct, NDA and POSH policy.</li>
</ul>

<p>Please countersign and return a copy of this letter as a token of your acceptance.</p>

<p>We welcome you and look forward to a long and mutually beneficial association.</p>

@include('reports.hr-letters._footer')
