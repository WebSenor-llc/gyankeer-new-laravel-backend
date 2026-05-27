@include('reports.hr-letters._header')

<p><strong>TO WHOMSOEVER IT MAY CONCERN</strong></p>

<p>This is to acknowledge that <strong>{{ $e->full_name ?: trim(($e->first_name ?? '').' '.($e->last_name ?? '')) }}</strong>
    (Emp Code: {{ $e->emp_code ?: $e->emp_id }}),
    who was working as <strong>{{ $e->designation->designation_name ?? '—' }}</strong> in the
    <strong>{{ $e->department->dept_name ?? '—' }}</strong> department of
    <strong>{{ $c->company_name ?? '' }}</strong>,
    has been relieved from {{ in_array(strtolower($e->gender ?? ''), ['female','f']) ? 'her' : 'his' }}
    duties with effect from the close of business on
    <strong>{{ $e->date_of_relieving ? \Carbon\Carbon::parse($e->date_of_relieving)->format('d M Y') : $today->format('d M Y') }}</strong>.</p>

<p>All company assets, identity cards and confidential records under {{ in_array(strtolower($e->gender ?? ''), ['female','f']) ? 'her' : 'his' }}
    custody have been returned, and full and final settlement of dues has been / will be processed in
    accordance with company policy and applicable statutes (Payment of Wages Act, EPF & MP Act, Payment of
    Gratuity Act where applicable).</p>

<p>We thank {{ $e->first_name ?: 'them' }} for the services rendered and wish {{ in_array(strtolower($e->gender ?? ''), ['female','f']) ? 'her' : 'him' }}
    success in all future endeavours.</p>

@include('reports.hr-letters._footer')
