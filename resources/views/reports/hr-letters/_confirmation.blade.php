@include('reports.hr-letters._header')

<p>Dear {{ $e->first_name ?: $e->full_name }},</p>

<p>We are pleased to inform you that based on the review of your performance, conduct and contribution during the
    probationary period, your services as <strong>{{ $e->designation->designation_name ?? '—' }}</strong>
    with <strong>{{ $c->company_name ?? '' }}</strong> are hereby <strong>confirmed</strong> with effect from
    <strong>{{ $today->format('d M Y') }}</strong>.</p>

<p>All other terms and conditions of your appointment letter shall remain unchanged unless specifically
    communicated to you in writing.</p>

<p>We take this opportunity to congratulate you on the confirmation and look forward to your continued
    dedication, professional growth and contribution to the organisation.</p>

@include('reports.hr-letters._footer')
