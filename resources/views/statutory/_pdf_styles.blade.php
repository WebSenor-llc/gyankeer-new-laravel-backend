{{-- Shared print stylesheet for all statutory return PDFs.
     Browser-side: window.print() → Save as PDF (no dependency). --}}
<style>
    @page { size: A4 landscape; margin: 8mm; }
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11.5px; margin: 0; padding: 8px; background: #f1f5f9; color: #000; }
    .actions { max-width: 1700px; margin: 0 auto 8px; display: flex; gap: 8px; justify-content: flex-end }
    .actions button { padding: 6px 14px; background: #DC2626; color: #fff; border: none; border-radius: 3px; cursor: pointer; font-size: 13px }
    .actions button.secondary { background: #64748B }
    .sheet { background: #fff; max-width: 1700px; margin: 0 auto; padding: 8mm; border: 1px solid #ccc; }
    .gov-header { border: 2px solid #000; padding: 6px 10px; margin-bottom: 8px; }
    .gov-header .top { display: flex; justify-content: space-between; align-items: flex-start; }
    .gov-header h1 { font-size: 16px; font-weight: 800; margin: 0; text-transform: uppercase; letter-spacing: 0.4px; }
    .gov-header h2 { font-size: 13px; font-weight: 700; margin: 1px 0 0; }
    .gov-header .form-no { font-weight: 700; font-size: 13px; text-align: right; }
    .gov-header .form-no small { display: block; font-weight: 500; font-size: 10px; }
    .gov-header .meta { display: grid; grid-template-columns: 2fr 1fr; gap: 6px; margin-top: 6px; font-size: 11px; }
    .gov-header .meta div { line-height: 1.4 }
    .gov-header .meta strong { display: inline-block; min-width: 130px; }
    .gov-header hr { border: 0; border-top: 1px dashed #555; margin: 6px 0; }
    table.gov-table { width: 100%; border-collapse: collapse; font-size: 11px; }
    table.gov-table th, table.gov-table td { border: 1px solid #000; padding: 4px 5px; vertical-align: top; }
    table.gov-table thead th { background: #E5E7EB; font-weight: 700; font-size: 10.5px; text-align: center; line-height: 1.2; }
    table.gov-table td.l { text-align: left; }
    table.gov-table td.r { text-align: right; }
    table.gov-table td.c { text-align: center; }
    tr.totals-row td { background: #FEF3C7; font-weight: 800; }
    tr.totals-row td.label { background: #FCD34D; text-align: center; }
    .summary-box { display: grid; grid-template-columns: repeat(6, 1fr); gap: 4px; margin: 8px 0 4px; }
    .summary-box .cell { border: 1px solid #000; padding: 4px 6px; font-size: 10.5px; }
    .summary-box .cell .lbl { display: block; font-weight: 700; font-size: 9.5px; text-transform: uppercase; color: #444; }
    .summary-box .cell .val { display: block; font-weight: 800; font-size: 12.5px; margin-top: 1px; }
    .summary-box .cell.total { background: #FEF2F2; border-color: #B91C1C; }
    .summary-box .cell.total .val { color: #B91C1C; }
    .sigs { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 18px; font-size: 11px; }
    .sigs .blk { border-top: 1px solid #000; padding-top: 4px; min-height: 40px; }
    .sigs .blk .lbl { font-size: 10px; color: #555; }
    .declaration { font-size: 10.5px; margin-top: 10px; line-height: 1.4 }
    .declaration b { font-weight: 700 }
    .footer { display: flex; justify-content: space-between; margin-top: 8px; font-size: 9px; padding: 0 4px; color: #555; }
    @media print {
        body { background: #fff; padding: 0; }
        .actions { display: none; }
        .sheet { border: none; max-width: 100%; padding: 0; }
    }
</style>
