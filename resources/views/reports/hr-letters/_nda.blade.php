@include('reports.hr-letters._header')

<p><strong>NON-DISCLOSURE & CONFIDENTIALITY AGREEMENT</strong></p>

<p>This Non-Disclosure Agreement ("Agreement") is entered into on <strong>{{ $today->format('d M Y') }}</strong>
    between <strong>{{ $c->company_name ?? '' }}</strong>
    @if($c?->cin)(CIN: {{ $c->cin }})@endif,
    having its registered office at
    {{ $c->registered_address_line1 ?? '' }}@if($c?->city), {{ $c->city }}@endif
    (hereinafter "Company"), and
    <strong>{{ $e->full_name ?: trim(($e->first_name ?? '').' '.($e->last_name ?? '')) }}</strong>
    (Emp Code: {{ $e->employee_code ?: $e->emp_id }}) (hereinafter "Employee").</p>

<p><strong>1. Confidential Information.</strong> The Employee acknowledges that during the course of employment
    {{ in_array(strtolower($e->gender ?? ''), ['female','f']) ? 'she' : 'he' }} may have access to and become acquainted with
    trade secrets, business plans, customer lists, source code, payroll data, statutory filings, financial
    information and other confidential information belonging to the Company or its clients.</p>

<p><strong>2. Non-Disclosure.</strong> The Employee agrees to hold all Confidential Information in strict
    confidence and shall not, directly or indirectly, disclose, publish, copy or use such information
    for any purpose other than the discharge of {{ in_array(strtolower($e->gender ?? ''), ['female','f']) ? 'her' : 'his' }} duties to the Company.</p>

<p><strong>3. Data Protection.</strong> The Employee shall handle personal and sensitive personal data in
    accordance with the Digital Personal Data Protection Act, 2023 (DPDP) and the Company's internal data
    handling policies, including retention and erasure norms.</p>

<p><strong>4. Return of Materials.</strong> Upon termination of employment, the Employee shall return all
    documents, devices, credentials and copies of Confidential Information in {{ in_array(strtolower($e->gender ?? ''), ['female','f']) ? 'her' : 'his' }} possession.</p>

<p><strong>5. Survival.</strong> The obligations under this Agreement shall survive the termination of
    employment for a period of three (3) years or such longer period as may be required by law.</p>

<p><strong>6. Governing Law.</strong> This Agreement shall be governed by the laws of India and any dispute
    arising hereunder shall be subject to the exclusive jurisdiction of the courts at
    {{ $c->city ?? '____________' }}.</p>

<br>
<table class="address" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:50%;">
            <p><strong>Employee</strong></p>
            <br><br>
            <p>_________________________<br>
            {{ $e->full_name }}<br>
            Emp Code: {{ $e->employee_code ?: $e->emp_id }}</p>
        </td>
        <td style="width:50%;">
            <p><strong>For {{ $c->company_name ?? '' }}</strong></p>
            <br><br>
            <p>_________________________<br>
            {{ $c->authorized_signatory_name ?? 'Authorised Signatory' }}<br>
            {{ $c->authorized_signatory_designation ?? '' }}</p>
        </td>
    </tr>
</table>
