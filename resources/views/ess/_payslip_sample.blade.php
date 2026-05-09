@php
$basic = $emp->current_basic ?? 0;
$hra   = $emp->current_hra ?? 0;
$da    = $emp->current_da ?? 0;
$conv  = is_numeric($emp->current_conv ?? 0) ? $emp->current_conv : 0;
$med   = is_numeric($emp->current_med ?? 0) ? $emp->current_med : 0;
$spl   = $emp->current_spl ?? 0;
$gross = $emp->current_gross ?? ($basic + $hra + $da + $conv + $med + $spl);

// Indian statutory calc (sample)
$pfWage = min($basic + $da, 15000);
$pf     = round($pfWage * 0.12, 2);
$esi    = $gross <= 21000 ? round($gross * 0.0075, 2) : 0;
$pt     = 200; // typical Maharashtra/Karnataka monthly
$lwf    = 6;   // Maharashtra example
$tds    = 0;   // depends on declared investments — set 0 for sample
$totDed = $pf + $esi + $pt + $lwf + $tds;
$net    = $gross - $totDed;
@endphp
<div class="border border-[var(--line)] rounded-lg p-4 bg-white">
    <div class="text-xs text-amber-700 font-semibold mb-2">📋 Sample only — based on master data, not a posted payroll run.</div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <div class="font-semibold mb-2 text-sm">EARNINGS</div>
            <table class="grid-tbl text-sm">
                <tr><td>Basic</td><td class="text-right">&#8377;{{ number_format($basic, 0) }}</td></tr>
                <tr><td>HRA</td><td class="text-right">&#8377;{{ number_format($hra, 0) }}</td></tr>
                <tr><td>DA</td><td class="text-right">&#8377;{{ number_format($da, 0) }}</td></tr>
                <tr><td>Conveyance</td><td class="text-right">&#8377;{{ number_format($conv, 0) }}</td></tr>
                <tr><td>Medical</td><td class="text-right">&#8377;{{ number_format($med, 0) }}</td></tr>
                <tr><td>Special</td><td class="text-right">&#8377;{{ number_format($spl, 0) }}</td></tr>
                <tr style="background:#FEF2F2"><th>Gross</th><th class="text-right">&#8377;{{ number_format($gross, 0) }}</th></tr>
            </table>
        </div>
        <div>
            <div class="font-semibold mb-2 text-sm">DEDUCTIONS</div>
            <table class="grid-tbl text-sm">
                <tr><td>EPF (12%)</td><td class="text-right">&#8377;{{ number_format($pf, 0) }}</td></tr>
                <tr><td>ESI (0.75%)</td><td class="text-right">&#8377;{{ number_format($esi, 0) }}</td></tr>
                <tr><td>PT</td><td class="text-right">&#8377;{{ number_format($pt, 0) }}</td></tr>
                <tr><td>LWF</td><td class="text-right">&#8377;{{ number_format($lwf, 0) }}</td></tr>
                <tr><td>TDS</td><td class="text-right">&#8377;{{ number_format($tds, 0) }}</td></tr>
                <tr style="background:#FEF2F2"><th>Total</th><th class="text-right">&#8377;{{ number_format($totDed, 0) }}</th></tr>
            </table>
        </div>
    </div>
    <div class="mt-3 p-3 rounded-lg" style="background:var(--brand);color:white">
        <div class="flex items-center justify-between">
            <span class="text-xs uppercase opacity-80">Net Pay (sample)</span>
            <span class="text-xl font-bold">&#8377;{{ number_format($net, 0) }}</span>
        </div>
    </div>
</div>
