<div class="company-block">
    <div class="company-name">{{ $c->company_name ?? 'Company Name' }}</div>
    @if(!empty($c?->registered_address_line1) || !empty($c?->city))
        <div class="company-meta">
            {{ $c->registered_address_line1 }}@if($c?->registered_address_line2), {{ $c->registered_address_line2 }}@endif
            @if($c?->city), {{ $c->city }}@endif
            @if($c?->state), {{ $c->state }}@endif
            @if($c?->pin_code) - {{ $c->pin_code }}@endif
        </div>
    @endif
    <div class="company-meta">
        @if($c?->phone)Phone: {{ $c->phone }}@endif
        @if($c?->email) | Email: {{ $c->email }}@endif
        @if($c?->cin) | CIN: {{ $c->cin }}@endif
    </div>
</div>

<div class="ref-row">
    <div>Ref: HR/{{ now()->format('Y') }}/{{ str_pad($e->emp_id, 5, '0', STR_PAD_LEFT) }}</div>
    <div>Date: {{ $today->format('d M Y') }}</div>
</div>

<h1 class="letter-title">{{ $title }}</h1>

<div class="salutation">
    <strong>{{ $e->full_name ?: trim(($e->first_name ?? '').' '.($e->last_name ?? '')) }}</strong><br>
    @if($e->permanent_address_line1){{ $e->permanent_address_line1 }}<br>@endif
    @if($e->permanent_address_line2){{ $e->permanent_address_line2 }}<br>@endif
    Emp Code: {{ $e->employee_code ?: $e->emp_id }}
</div>
