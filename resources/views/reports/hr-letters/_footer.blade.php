<div class="signature-block">
    <p>For <strong>{{ $c->company_name ?? '' }}</strong></p>
    <br><br>
    <p>_________________________<br>
    {{ $c->authorized_signatory_name ?? 'Authorised Signatory' }}<br>
    {{ $c->authorized_signatory_designation ?? 'HR Department' }}</p>
</div>
