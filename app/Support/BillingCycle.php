<?php

namespace App\Support;

use Carbon\Carbon;

class BillingCycle
{
    /**
     * Build due date for a month/year.
     * If the configured day is 30 or higher, use the last business day of the month.
     */
    public static function makeDueDate(int $year, int $month, int $day): Carbon
    {
        $base = Carbon::create($year, $month, 1)->startOfDay();
        $normalizedDay = max(1, $day);

        if ($normalizedDay >= 30) {
            return self::lastBusinessDayOfMonth($year, $month);
        }

        $normalizedDay = min($normalizedDay, $base->daysInMonth);

        return Carbon::create($year, $month, $normalizedDay)->startOfDay();
    }

    /**
     * Resolve reference month/year using due date rule.
     * If due date is at month end (or near-end configured day), reference is the next month.
     */
    public static function referenceDate(int $year, int $month, ?Carbon $dueDate = null): Carbon
    {
        if ($dueDate && ($dueDate->day >= 30 || self::isLastDayOfMonth($dueDate) || self::isLastBusinessDay($dueDate))) {
            return $dueDate->copy()->addMonthNoOverflow()->startOfMonth();
        }

        return Carbon::create($year, $month, 1)->startOfMonth();
    }

    public static function isLastDayOfMonth(Carbon $date): bool
    {
        return $date->isSameDay($date->copy()->endOfMonth()->startOfDay());
    }

    public static function isLastBusinessDay(Carbon $date): bool
    {
        return $date->isSameDay(self::lastBusinessDayOfMonth($date->year, $date->month));
    }

    public static function lastBusinessDayOfMonth(int $year, int $month): Carbon
    {
        $date = Carbon::create($year, $month, 1)->endOfMonth()->startOfDay();

        while ($date->isWeekend()) {
            $date->subDay();
        }

        return $date;
    }
}
