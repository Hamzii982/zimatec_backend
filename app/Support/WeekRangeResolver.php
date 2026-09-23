<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Builds the "last N calendar weeks" picker list and resolves the
 * from/to date range for whichever week is currently selected.
 *
 * This logic was previously duplicated byte-for-byte across four
 * TimeController methods (records, compare, machineLogs, weeklyOverview).
 * Extracting it keeps all four call sites in sync and makes it unit
 * testable in isolation.
 */
class WeekRangeResolver
{
    private const MAX_WEEKS = 5;

    public function resolve(?string $requestedWeek): WeekRange
    {
        $today = Carbon::now();
        $selectedWeek = $requestedWeek ?: $today->format('oW');

        $weeks = [];
        $i = 0;

        while (true) {
            $weekStart = (clone $today)->startOfWeek()->subWeeks($i);
            $weekNumber = $weekStart->format('oW');

            $weeks[] = [
                'label' => 'KW '.$weekStart->format('W').' / '.$weekStart->format('o'),
                'value' => $weekNumber,
            ];

            $i++;

            // Stop conditions:
            // 1. Reached maxWeeks AND selectedWeek is already in the list
            // 2. Or the last generated week matches selectedWeek
            if (count($weeks) >= self::MAX_WEEKS && in_array($selectedWeek, array_column($weeks, 'value'), true)) {
                break;
            }

            if ($weekNumber === $selectedWeek) {
                break;
            }
        }

        $year = substr($selectedWeek, 0, 4);
        $week = substr($selectedWeek, 4, 2);

        $fromDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $toDate = Carbon::now()->setISODate($year, $week)->endOfWeek();

        return new WeekRange($weeks, $selectedWeek, $fromDate, $toDate);
    }
}