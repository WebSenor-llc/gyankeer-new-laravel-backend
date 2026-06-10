<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Hreasy by WebSenor — per-employee weekly-off weekday resolver.
 *
 * Each employee may rest on a different weekday, stored as a short code in
 * employees.weekly_off_pattern (Sun|Mon|Tue|Wed|Thu|Fri|Sat). A blank/unknown
 * pattern falls back to Sunday, preserving the system's original behaviour.
 *
 * Single source of truth: attendance auto-fill (LeaveAttendanceSync, the
 * summary / quick-counts save paths) resolves the off-day through here instead
 * of hardcoding Sunday.
 */
class WeeklyOff
{
    /** code => Carbon dayOfWeek int (0 = Sun .. 6 = Sat). */
    private const MAP = [
        'SUN' => Carbon::SUNDAY,
        'MON' => Carbon::MONDAY,
        'TUE' => Carbon::TUESDAY,
        'WED' => Carbon::WEDNESDAY,
        'THU' => Carbon::THURSDAY,
        'FRI' => Carbon::FRIDAY,
        'SAT' => Carbon::SATURDAY,
    ];

    /** Dropdown options for the employee form: ['Sun' => 'Sunday', ...]. */
    public static function options(): array
    {
        return [
            'Sun' => 'Sunday',
            'Mon' => 'Monday',
            'Tue' => 'Tuesday',
            'Wed' => 'Wednesday',
            'Thu' => 'Thursday',
            'Fri' => 'Friday',
            'Sat' => 'Saturday',
        ];
    }

    /** Resolve a stored pattern to a Carbon dayOfWeek int; blank/unknown => Sunday. */
    public static function dayOfWeek(?string $pattern): int
    {
        $key = strtoupper(substr(trim((string) $pattern), 0, 3));
        return self::MAP[$key] ?? Carbon::SUNDAY;
    }

    /** Day-numbers in the given month that fall on the employee's off weekday. */
    public static function daysInMonth(?string $pattern, int $year, int $month): array
    {
        $dow   = self::dayOfWeek($pattern);
        $total = (int) Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $days = [];
        for ($d = 1; $d <= $total; $d++) {
            if (Carbon::createFromDate($year, $month, $d)->dayOfWeek === $dow) {
                $days[] = $d;
            }
        }
        return $days;
    }

    /** Count of weekly-off days in the month (the default W count). */
    public static function countInMonth(?string $pattern, int $year, int $month): int
    {
        return count(self::daysInMonth($pattern, $year, $month));
    }
}
