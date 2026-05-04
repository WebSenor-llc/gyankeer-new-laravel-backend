<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StatutorySlab extends Model
{
    protected $table = 'statutory_slabs';
    protected $guarded = [];
    protected $casts = [
        'value_json'    => 'array',
        'value_decimal' => 'decimal:4',
        'active_flag'   => 'boolean',
        'fy_start_year' => 'integer',
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
