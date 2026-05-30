<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-update existing employees from a CSV, matched by emp_id.
 *
 *   php artisan employees:import-csv                 # uses empdata/employees.csv
 *   php artisan employees:import-csv path/to.csv --dry-run
 *
 * Semantics (per product decision):
 *   • Match on emp_id. emp_ids not found in the table are skipped + reported
 *     (never created).
 *   • Fill-only: blank cells (empty OR the literal string "NULL") never overwrite
 *     an existing DB value.
 *   • Only the columns in $map are touched; any other CSV column is ignored
 *     (and listed). emp_id itself is the key and is never written.
 *
 * Type handling:
 *   • DOB / date_of_joining / date_of_confirmation / date_of_retirement parsed
 *     from DD-MM-YY (2-digit-year pivot: 00-68 -> 20xx, 69-99 -> 19xx).
 *   • Sex M/F -> gender Male/Female.   • pan_no upper-cased.   • on_probation 1/0.
 */
class ImportEmployeeCsv extends Command
{
    protected $signature = 'employees:import-csv
        {path=empdata/employees.csv}
        {--dry-run}';

    protected $description = 'Update employees from a CSV, matched by emp_id (fill-only, skips blanks)';

    /** CSV header => employees column. Anything not listed here is ignored. */
    private array $map = [
        'full_name'               => 'full_name',
        'fathers_name'            => 'fathers_name',
        'mothers_name'            => 'mothers_name',
        'HusName'                 => 'spouse_name',
        'Sex'                     => 'gender',
        'DOB'                     => 'dob',
        'aadhar_id_no'            => 'aadhar_id_no',
        'pan_no'                  => 'pan_no',
        'job_description'         => 'job_description',
        'date_of_joining'         => 'date_of_joining',
        'on_probation'            => 'on_probation',
        'probation_period_months' => 'probation_period_months',
        'date_of_confirmation'    => 'date_of_confirmation',
        'personal_mobile'         => 'personal_mobile',
        'permanent_address_line1' => 'permanent_address_line1',
        'permanent_address_line2' => 'permanent_address_line2',
        'date_of_retirement'      => 'date_of_retirement',
    ];

    /** Target columns that must be parsed as DD-MM-YY dates. */
    private array $dateCols = ['dob', 'date_of_joining', 'date_of_confirmation', 'date_of_retirement'];

    /**
     * Max length of the constrained string columns we write. A CSV value longer
     * than this (e.g. a malformed 11-char PAN) is skipped + reported rather than
     * truncated (truncation would silently corrupt the value) or written (which
     * throws "Data too long" and aborts the whole transaction).
     */
    private array $maxLen = [
        'full_name'       => 200,
        'fathers_name'    => 150,
        'mothers_name'    => 150,
        'spouse_name'     => 150,
        'gender'          => 20,
        'aadhar_id_no'    => 12,
        'pan_no'          => 10,
        'personal_mobile' => 20,
    ];

    public function handle(): int
    {
        $path = $this->argument('path');
        if (!str_starts_with($path, '/')) {
            $path = base_path($path);
        }
        if (!file_exists($path)) {
            $this->error("CSV not found: {$path}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $fh = fopen($path, 'r');
        $header = fgetcsv($fh);
        if (!$header) {
            $this->error('CSV is empty or unreadable.');
            return self::FAILURE;
        }
        // Strip a UTF-8 BOM from the first header cell if present.
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        // header label (trimmed, original case) => column index
        $idx = [];
        foreach ($header as $i => $h) {
            $idx[trim((string) $h)] = $i;
        }
        if (!isset($idx['emp_id'])) {
            $this->error("CSV header is missing 'emp_id'. Got: " . implode(', ', $header));
            return self::FAILURE;
        }

        // Mapped columns actually present in this file.
        $present = [];
        foreach ($this->map as $csvCol => $dbCol) {
            if (isset($idx[$csvCol])) {
                $present[$csvCol] = $dbCol;
            }
        }
        $ignored = array_values(array_diff(
            array_keys($idx),
            array_merge(['emp_id'], array_keys($present))
        ));

        $rows = $matched = $updated = $unchanged = 0;
        $unmatched = [];
        $warnings  = [];

        $apply = function () use ($fh, $idx, $present, $dryRun, &$rows, &$matched, &$updated, &$unchanged, &$unmatched, &$warnings) {
            while (($r = fgetcsv($fh)) !== false) {
                if (count($r) === 1 && trim((string) $r[0]) === '') {
                    continue; // blank line
                }
                $rows++;

                $empId = (int) trim((string) ($r[$idx['emp_id']] ?? ''));
                if (!$empId) {
                    continue;
                }

                $emp = Employee::where('emp_id', $empId)->first();
                if (!$emp) {
                    $unmatched[] = $empId;
                    continue;
                }
                $matched++;

                $updates = [];
                foreach ($present as $csvCol => $dbCol) {
                    $val = $this->normalize($r[$idx[$csvCol]] ?? null);
                    if ($val === null) {
                        continue; // fill-only: skip blanks / "NULL"
                    }
                    $val = $this->transform($dbCol, $val, $empId, $warnings);
                    if ($val === null) {
                        continue; // parse failure (already warned)
                    }
                    $updates[$dbCol] = $val;
                }

                if (!$updates) {
                    continue;
                }

                $emp->fill($updates);
                if (!$emp->isDirty()) {
                    $unchanged++;
                    continue;
                }
                if (!$dryRun) {
                    $emp->save();
                }
                $updated++;
            }
        };

        if ($dryRun) {
            $apply();
        } else {
            DB::transaction($apply);
        }
        fclose($fh);

        // ── Report ──
        $this->newLine();
        $this->info(($dryRun ? '[DRY-RUN] ' : '') . 'Employee CSV import summary');
        $this->table(['metric', 'count'], [
            ['data rows',          $rows],
            ['matched emp_id',     $matched],
            ['rows updated',       $updated],
            ['matched no-change',  $unchanged],
            ['unmatched emp_id',   count(array_unique($unmatched))],
        ]);

        if ($ignored) {
            $this->warn('Ignored CSV columns (no mapping): ' . implode(', ', $ignored));
        }
        if ($unmatched) {
            $u = array_values(array_unique($unmatched));
            $this->warn('Unmatched emp_ids (' . count($u) . '): '
                . implode(', ', array_slice($u, 0, 50)) . (count($u) > 50 ? ', …' : ''));
        }
        if ($warnings) {
            $this->warn('Parse warnings (' . count($warnings) . '):');
            foreach (array_slice($warnings, 0, 30) as $w) {
                $this->line('  • ' . $w);
            }
            if (count($warnings) > 30) {
                $this->line('  • … ' . (count($warnings) - 30) . ' more');
            }
        }
        if ($dryRun) {
            $this->warn('Dry-run — nothing written. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    /** Trim; treat '' and the literal "NULL" (any case) as missing -> null. */
    private function normalize($raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $v = trim((string) $raw);
        if ($v === '' || strcasecmp($v, 'NULL') === 0) {
            return null;
        }
        return $v;
    }

    /** Convert a non-blank value for its target column. Returns null to skip. */
    private function transform(string $dbCol, string $val, int $empId, array &$warnings)
    {
        if (in_array($dbCol, $this->dateCols, true)) {
            $d = $this->parseDate($val);
            if ($d === null) {
                $warnings[] = "emp {$empId}: bad date in {$dbCol} = '{$val}' (skipped)";
            }
            return $d;
        }

        $out = match ($dbCol) {
            'gender'                  => $this->mapGender($val),
            'pan_no'                  => strtoupper($val),
            'on_probation'            => in_array(strtolower($val), ['1', 'yes', 'true', 'y'], true) ? 1 : 0,
            'probation_period_months' => (int) $val,
            default                   => $val,
        };

        // Skip (don't truncate) string values that exceed the column length.
        if (is_string($out) && isset($this->maxLen[$dbCol]) && mb_strlen($out) > $this->maxLen[$dbCol]) {
            $warnings[] = "emp {$empId}: {$dbCol} = '{$out}' exceeds {$this->maxLen[$dbCol]} chars (skipped)";
            return null;
        }

        return $out;
    }

    /** Parse strict DD-MM-YY -> 'Y-m-d', or null if invalid. */
    private function parseDate(string $val): ?string
    {
        $d = Carbon::createFromFormat('!d-m-y', $val);
        $errors = Carbon::getLastErrors();
        if ($d === false || ($errors && (($errors['error_count'] ?? 0) > 0 || ($errors['warning_count'] ?? 0) > 0))) {
            return null;
        }
        return $d->format('Y-m-d');
    }

    private function mapGender(string $val): string
    {
        return match (strtoupper($val)) {
            'M', 'MALE'   => 'Male',
            'F', 'FEMALE' => 'Female',
            default       => $val,
        };
    }
}
