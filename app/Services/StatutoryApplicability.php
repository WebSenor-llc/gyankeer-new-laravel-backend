<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeStatutoryChange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-employee, per-period PF / EPS / ESI applicability.
 *
 * Source of truth: employee_statutory_changes (effective-dated remove/restore).
 *  - Latest row with effective_from <= period start decides.
 *  - Period before the first change → opposite of the first change's action.
 *  - No history at all → legacy employees.*_applicable_flag (default true).
 * PF not applicable ⇒ EPS not applicable.
 *
 * employees.pf_applicable_flag / fpf_applicable_flag / esi_applicable_flag
 * mirror the CURRENT month's state so legacy readers keep working.
 */
class StatutoryApplicability
{
    public const FLAG_COLUMNS = [
        'pf'  => 'pf_applicable_flag',
        'eps' => 'fpf_applicable_flag',
        'esi' => 'esi_applicable_flag',
    ];

    /** @var array<int, array<string, array<int, array{from:string, action:string}>>> emp_id → scheme → rows asc */
    protected array $cache = [];

    protected ?bool $tableExists = null;

    /** @var array<string,bool> */
    protected array $columnExists = [];

    /** Load history for many employees in one query (call before a payroll loop). */
    public function preload(iterable $empIds): void
    {
        $ids = [];
        foreach ($empIds as $id) {
            $id = (int) $id;
            if ($id > 0 && !array_key_exists($id, $this->cache)) $ids[] = $id;
        }
        if (!$ids) return;

        foreach ($ids as $id) $this->cache[$id] = [];
        if (!$this->hasTable()) return;

        foreach (array_chunk($ids, 1000) as $chunk) {
            EmployeeStatutoryChange::whereIn('emp_id', $chunk)
                ->orderBy('effective_from')->orderBy('id')
                ->get(['emp_id', 'scheme', 'action', 'effective_from'])
                ->each(function ($r) {
                    $this->cache[(int) $r->emp_id][$r->scheme][] = [
                        'from'   => $r->effective_from->format('Y-m-d'),
                        'action' => $r->action,
                    ];
                });
        }
    }

    public function forget(int $empId): void
    {
        unset($this->cache[$empId]);
    }

    /**
     * @param string $period 'Y-m' (e.g. 2026-12)
     */
    public function isApplicable(Employee $e, string $scheme, string $period): bool
    {
        if ($scheme === 'eps' && !$this->isApplicable($e, 'pf', $period)) {
            return false;
        }
        return $this->schemeState($e, $scheme, $period);
    }

    /** Scheme's own remove/restore state, ignoring the PF ⇒ EPS rule. */
    public function ownState(Employee $e, string $scheme, string $period): bool
    {
        return $this->schemeState($e, $scheme, $period);
    }

    /**
     * Status for UI: ['applicable' => bool, 'since' => ?Carbon (effective_from of deciding row)]
     */
    public function status(Employee $e, string $scheme, string $period): array
    {
        $rows = $this->rows((int) $e->emp_id, $scheme);
        $start = $this->periodStart($period);
        $since = null;
        foreach ($rows as $r) {
            if ($r['from'] <= $start) $since = Carbon::parse($r['from']);
        }
        return ['applicable' => $this->isApplicable($e, $scheme, $period), 'since' => $since];
    }

    /**
     * Record a remove/restore and sync the mirror flag.
     *
     * @param string $fromMonth 'Y-m'
     */
    public function change(Employee $e, string $scheme, string $action, string $fromMonth, ?string $reason, ?int $userId): EmployeeStatutoryChange
    {
        if (!in_array($scheme, EmployeeStatutoryChange::SCHEMES, true)) {
            throw new \InvalidArgumentException("Unknown scheme [$scheme]");
        }
        if (!in_array($action, ['removed', 'restored'], true)) {
            throw new \InvalidArgumentException("Unknown action [$action]");
        }

        return DB::transaction(function () use ($e, $scheme, $action, $fromMonth, $reason, $userId) {
            // Seed history from the legacy flag the first time, so months before this
            // change keep their old behaviour. E.g. flag already off (set on Manage
            // Salary config) and HR now restores from Dec → months before Dec stay off.
            $this->seedFromLegacyFlag($e, $scheme, $fromMonth);

            $row = EmployeeStatutoryChange::create([
                'emp_id'         => $e->emp_id,
                'company_id'     => $e->company_id,
                'scheme'         => $scheme,
                'action'         => $action,
                'effective_from' => $this->periodStart($fromMonth),
                'reason'         => $reason,
                'changed_by'     => $userId,
            ]);

            $this->forget((int) $e->emp_id);
            $this->syncFlags($e);

            return $row;
        });
    }

    /** Write current-month state back to employees.*_applicable_flag. */
    public function syncFlags(Employee $e): void
    {
        $now = now()->format('Y-m');
        $payload = [];
        foreach (self::FLAG_COLUMNS as $scheme => $col) {
            if ($this->hasColumn($col)) {
                $payload[$col] = $this->schemeState($e, $scheme, $now) ? 1 : 0;
            }
        }
        if ($payload) {
            Employee::withTrashed()->where('emp_id', $e->emp_id)->update($payload);
            foreach ($payload as $k => $v) $e->setAttribute($k, (bool) $v);
            $e->syncOriginalAttributes(array_keys($payload));
        }
    }

    /** Salary runs that already exist for months >= $fromMonth (for back-date warning). */
    public function affectedRuns(Employee $e, string $fromMonth): \Illuminate\Support\Collection
    {
        [$y, $m] = array_map('intval', explode('-', $fromMonth));
        return DB::table('salary_runs')
            ->where('company_id', $e->company_id)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($y, $m) {
                $q->where('period_year', '>', $y)
                  ->orWhere(fn($q) => $q->where('period_year', $y)->where('period_month', '>=', $m));
            })
            ->orderBy('period_year')->orderBy('period_month')
            ->get(['run_id', 'period_year', 'period_month', 'status']);
    }

    // ── internals ─────────────────────────────────────────────

    protected function schemeState(Employee $e, string $scheme, string $period): bool
    {
        $rows = $this->rows((int) $e->emp_id, $scheme);
        if (!$rows) return $this->legacyFlag($e, $scheme);

        $start = $this->periodStart($period);
        $state = null;
        foreach ($rows as $r) {
            if ($r['from'] <= $start) $state = $r['action'] === 'restored';
        }
        // Before the first recorded change: opposite of that change.
        return $state ?? ($rows[0]['action'] === 'removed');
    }

    protected function rows(int $empId, string $scheme): array
    {
        if (!array_key_exists($empId, $this->cache)) $this->preload([$empId]);
        return $this->cache[$empId][$scheme] ?? [];
    }

    protected function legacyFlag(Employee $e, string $scheme): bool
    {
        $col = self::FLAG_COLUMNS[$scheme];
        if (!$this->hasColumn($col)) return true;
        return ($e->getAttribute($col) ?? true) == true;
    }

    protected function seedFromLegacyFlag(Employee $e, string $scheme, string $fromMonth): void
    {
        if ($this->rows((int) $e->emp_id, $scheme)) return;
        if ($this->legacyFlag($e, $scheme)) return; // applicable before = default; nothing to seed

        // Legacy flag was off with no history → record "removed" from an early date.
        EmployeeStatutoryChange::create([
            'emp_id'         => $e->emp_id,
            'company_id'     => $e->company_id,
            'scheme'         => $scheme,
            'action'         => 'removed',
            'effective_from' => '2000-01-01',
            'reason'         => 'Migrated from existing setting',
            'changed_by'     => null,
        ]);
        $this->forget((int) $e->emp_id);
    }

    protected function periodStart(string $period): string
    {
        return Carbon::createFromFormat('Y-m-d', substr($period, 0, 7) . '-01')->format('Y-m-d');
    }

    protected function hasTable(): bool
    {
        return $this->tableExists ??= Schema::hasTable('employee_statutory_changes');
    }

    protected function hasColumn(string $col): bool
    {
        return $this->columnExists[$col] ??= Schema::hasColumn('employees', $col);
    }
}
