@include('reports.hr-letters._header')

<p><strong>TO WHOMSOEVER IT MAY CONCERN</strong></p>

<p>This is to certify that <strong>{{ $e->full_name ?: trim(($e->first_name ?? '').' '.($e->last_name ?? '')) }}</strong>
    (Emp Code: {{ $e->emp_code ?: $e->emp_id }}) was employed with
    <strong>{{ $c->company_name ?? '' }}</strong> as
    <strong>{{ $e->designation->designation_name ?? '—' }}</strong>
    in the <strong>{{ $e->department->dept_name ?? '—' }}</strong> department from
    <strong>{{ $e->date_of_joining ? \Carbon\Carbon::parse($e->date_of_joining)->format('d M Y') : '_____________' }}</strong>
    to
    <strong>{{ $e->date_of_relieving ? \Carbon\Carbon::parse($e->date_of_relieving)->format('d M Y') : $today->format('d M Y') }}</strong>.</p>

<p>During the tenure of employment with us, we found {{ in_array(strtolower($e->gender ?? ''), ['female','f']) ? 'her' : 'his' }}
    performance, conduct and character to be satisfactory.</p>

<p>We wish {{ $e->first_name ?: 'them' }} the very best for all future endeavours.</p>

@include('reports.hr-letters._footer')
