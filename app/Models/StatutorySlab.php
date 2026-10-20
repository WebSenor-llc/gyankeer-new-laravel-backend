<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class StatutorySlab extends Model
{
    protected $table = 'statutory_slabs';
    protected $guarded = [];
    protected $casts = [
        'value_json'     => 'array',
        'value_decimal'  => 'decimal:4',
        'active_flag'    => 'boolean',
        'fy_start_year'  => 'integer',
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    /**
     * Convenience: read a single decimal rate from the table, falling back to
     * the config default if not yet seeded.
     *
     * Cached for the request lifetime.
     */
    public static function rate(string $key, int $fy, $default = null)
    {
        $cacheKey = "stat_rate:{$key}:{$fy}";
        return Cache::remember($cacheKey, 60, function () use ($key, $fy, $default) {
            $row = static::where('category', 'rate')
                ->where('key', $key)
                ->where('fy_start_year', $fy)
                ->where('active_flag', true)
                ->first();
            return $row ? (float) $row->value_decimal : $default;
        });
    }

    /**
     * Period-aware lookup: resolve a rate active in a given YYYY-MM period.
     * Falls back to the fy_start_year row if no effective-dated row matches.
     */
    public static function rateAt(string $key, string $period, $default = null)
    {
        $cacheKey = "stat_rate_at:{$key}:{$period}";
        return Cache::remember($cacheKey, 60, function () use ($key, $period, $default) {
            [$y, $m] = array_map('intval', explode('-', $period));
            $periodStart = sprintf('%04d-%02d-01', $y, $m);
            $periodEnd   = date('Y-m-t', strtotime($periodStart));

            if (Schema::hasColumn('statutory_slabs', 'effective_from')) {
                $row = static::where('category', 'rate')
                    ->where('key', $key)
                    ->where('active_flag', true)
                    ->whereNotNull('effective_from')
                    ->where('effective_from', '<=', $periodEnd)
                    ->where(function ($q) use ($periodStart) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $periodStart);
                    })
                    ->orderByDesc('effective_from')
                    ->first();
                if ($row) return (float) $row->value_decimal;
            }

            // Fallback: FY-granularity row
            $fy = $m >= (int) config('hreasy.company.fy_start_month', 4) ? $y : $y - 1;
            return static::rate($key, $fy, $default);
        });
    }

    /**
     * Read all rows of a category for an FY (e.g. all PT slabs for MH).
     */
    public static function forCategory(string $category, int $fy)
    {
        return static::where('category', $category)
            ->where('fy_start_year', $fy)
            ->where('active_flag', true)
            ->get();
    }
}
