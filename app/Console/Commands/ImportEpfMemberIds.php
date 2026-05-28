<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 *   php artisan epf:import-member-ids
 *   php artisan epf:import-member-ids pfnumber/pf-ecr-april-2026.xls --out=storage/app/epf_updates
 *
 * Reads a UAN / EPF Member ID sheet, matches against employees.uan, and
 * emits a reviewable SQL file of UPDATE statements (one per matched row)
 * plus CSVs of unmatched / conflicting / no-op rows. Does NOT touch the DB.
 */
class ImportEpfMemberIds extends Command
{
    protected $signature = 'epf:import-member-ids
                            {file=pfnumber/pf-ecr-april-2026.xls : Path to .xls/.xlsx (relative to base_path)}
                            {--out=storage/app/epf_updates : Output dir (relative to base_path)}';

    protected $description = 'Parse PF ECR sheet and generate SQL to backfill employees.epf_member_id from UAN.';

    public function handle(): int
    {
        $file = base_path($this->argument('file'));
        $outDir = base_path($this->option('out'));

        if (! is_file($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }
        if (! is_dir($outDir) && ! mkdir($outDir, 0775, true) && ! is_dir($outDir)) {
            $this->error("Cannot create output dir: {$outDir}");
            return self::FAILURE;
        }

        $this->info("Reading {$file} ...");
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);

        $sheetsWithData = 0;
        foreach ($spreadsheet->getAllSheets() as $s) {
            if ($s->getHighestDataRow() > 1) $sheetsWithData++;
        }
        if ($sheetsWithData > 1) {
            $this->warn("Workbook has {$sheetsWithData} sheets with data — using the first only.");
        }

        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, false);
        if (empty($rows)) {
            $this->error('Sheet is empty.');
            return self::FAILURE;
        }

        [$headerIdx, $uanCol, $memberCol] = $this->locateHeader($rows);
        if ($uanCol === null || $memberCol === null) {
            $this->error('Could not find UAN / EPF Member ID columns in any header row.');
            return self::FAILURE;
        }
        $this->info("Header row {$headerIdx}: UAN=col {$uanCol}, EPF Member ID=col {$memberCol}");

        $empMap = Employee::query()
            ->whereNotNull('uan')
            ->where('uan', '!=', '')
            ->get(['emp_id', 'uan', 'epf_member_id'])
            ->keyBy(fn ($e) => $this->normalizeUan($e->uan));

        $updates = [];
        $unmatched = [];
        $conflicts = [];
        $noop = [];
        $invalidValue = [];
        $dataRows = 0;

        $valuePattern = '/^[A-Za-z0-9\/\-]+$/';

        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rawUan = $row[$uanCol] ?? null;
            $rawMember = $row[$memberCol] ?? null;
            if (($rawUan === null || $rawUan === '') && ($rawMember === null || $rawMember === '')) continue;

            $dataRows++;
            $uan = $this->normalizeUan($rawUan);
            $member = trim((string) $rawMember);

            if ($uan === '' || $member === '') {
                $unmatched[] = [$i + 1, $rawUan, $rawMember, 'blank-uan-or-member'];
                continue;
            }

            if (! preg_match($valuePattern, $member)) {
                $invalidValue[] = [$i + 1, $uan, $member];
                continue;
            }

            $emp = $empMap->get($uan);
            if (! $emp) {
                $unmatched[] = [$i + 1, $uan, $member, 'no-employee'];
                continue;
            }

            $current = trim((string) $emp->epf_member_id);
            if ($current === '') {
                $updates[] = [$uan, $member, $emp->emp_id];
            } elseif ($current === $member) {
                $noop[] = [$uan, $member, $emp->emp_id];
            } else {
                $conflicts[] = [$uan, $current, $member, $emp->emp_id];
            }
        }

        $this->writeSql("{$outDir}/update_epf_member_id.sql", $updates, basename($file));
        $this->writeCsv("{$outDir}/unmatched.csv", ['sheet_row', 'uan', 'epf_member_id', 'reason'], $unmatched);
        $this->writeCsv("{$outDir}/conflicts.csv", ['uan', 'db_value', 'sheet_value', 'emp_id'], $conflicts);
        $this->writeCsv("{$outDir}/noop.csv", ['uan', 'value', 'emp_id'], $noop);
        $this->writeCsv("{$outDir}/invalid_values.csv", ['sheet_row', 'uan', 'sheet_value'], $invalidValue);

        $summary = [
            'source' => basename($file),
            'data_rows' => $dataRows,
            'updates_queued' => count($updates),
            'unmatched' => count($unmatched),
            'conflicts' => count($conflicts),
            'noop_already_correct' => count($noop),
            'invalid_value_format' => count($invalidValue),
            'generated_at' => now()->toDateTimeString(),
        ];
        file_put_contents("{$outDir}/summary.txt", $this->summaryText($summary));

        $this->newLine();
        $this->info('=== Summary ===');
        foreach ($summary as $k => $v) $this->line(sprintf('  %-22s %s', $k, $v));
        $this->newLine();
        $this->info("SQL written to: {$outDir}/update_epf_member_id.sql");
        $this->info("Review the .sql, then run inside a transaction on production.");

        return self::SUCCESS;
    }

    private function locateHeader(array $rows): array
    {
        $scanLimit = min(10, count($rows));
        for ($r = 0; $r < $scanLimit; $r++) {
            $row = $rows[$r];
            $uanCol = null;
            $memberCol = null;
            foreach ($row as $c => $cell) {
                $norm = strtolower(trim((string) $cell));
                $norm = preg_replace('/\s+/', ' ', $norm);
                if ($norm === 'uan' || $norm === 'uan number' || $norm === 'uan no') {
                    $uanCol = $c;
                } elseif ($norm === 'epf member id' || $norm === 'member id' || $norm === 'pf member id' || $norm === 'epf id') {
                    $memberCol = $c;
                }
            }
            if ($uanCol !== null && $memberCol !== null) {
                return [$r, $uanCol, $memberCol];
            }
        }
        return [0, null, null];
    }

    private function normalizeUan(mixed $raw): string
    {
        if ($raw === null) return '';
        if (is_float($raw)) {
            $raw = number_format($raw, 0, '.', '');
        }
        $s = trim((string) $raw);
        $s = preg_replace('/\s+/', '', $s);
        if (preg_match('/^\d+(\.\d+)?[eE]\+?\d+$/', $s)) {
            $s = number_format((float) $s, 0, '.', '');
        }
        return $s;
    }

    private function writeSql(string $path, array $updates, string $source): void
    {
        $fh = fopen($path, 'w');
        $now = now()->toDateTimeString();
        $n = count($updates);

        fwrite($fh, "-- Generated {$now} from {$source}\n");
        fwrite($fh, "-- Updates: {$n} rows\n");
        fwrite($fh, "-- Idempotent: WHERE guard prevents overwriting rows that became non-empty since generation.\n");
        fwrite($fh, "-- Run order: pre-check -> START TRANSACTION -> verify counts -> COMMIT (or ROLLBACK).\n\n");

        if ($n === 0) {
            fwrite($fh, "-- Nothing to update.\n");
            fclose($fh);
            return;
        }

        $uanList = implode(',', array_map(fn ($u) => "'" . $this->sqlEscape($u[0]) . "'", $updates));
        fwrite($fh, "-- Pre-check (run BEFORE the transaction):\n");
        fwrite($fh, "SELECT COUNT(*) AS will_match\n");
        fwrite($fh, "FROM employees\n");
        fwrite($fh, "WHERE uan IN ({$uanList})\n");
        fwrite($fh, "  AND (epf_member_id IS NULL OR epf_member_id = '');\n\n");

        fwrite($fh, "START TRANSACTION;\n\n");
        foreach ($updates as [$uan, $member, $empId]) {
            $u = $this->sqlEscape($uan);
            $m = $this->sqlEscape($member);
            fwrite($fh, "UPDATE employees SET epf_member_id = '{$m}'\n");
            fwrite($fh, " WHERE uan = '{$u}' AND (epf_member_id IS NULL OR epf_member_id = ''); -- emp_id={$empId}\n");
        }

        fwrite($fh, "\n-- Post-check (run BEFORE deciding COMMIT vs ROLLBACK):\n");
        fwrite($fh, "SELECT COUNT(*) AS now_set\n");
        fwrite($fh, "FROM employees\n");
        fwrite($fh, "WHERE epf_member_id IS NOT NULL AND epf_member_id <> '';\n\n");
        fwrite($fh, "COMMIT;\n");
        fwrite($fh, "-- ROLLBACK; -- use instead of COMMIT if counts look wrong\n");
        fclose($fh);
    }

    private function writeCsv(string $path, array $header, array $rows): void
    {
        $fh = fopen($path, 'w');
        fputcsv($fh, $header);
        foreach ($rows as $r) fputcsv($fh, $r);
        fclose($fh);
    }

    private function summaryText(array $s): string
    {
        $out = '';
        foreach ($s as $k => $v) $out .= sprintf("%-22s %s\n", $k, $v);
        return $out;
    }

    private function sqlEscape(string $s): string
    {
        return str_replace("'", "''", $s);
    }
}
