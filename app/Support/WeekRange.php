<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Immutable value object describing a selected calendar week and the
 * list of recent weeks offered for selection (used by the week-picker
 * dropdowns across the Time module views).
 */
class WeekRange
{
    /**
     * @param array<int, array{label: string, value: string}> $weeks
     */
    public function __construct(
        public readonly array $weeks,
        public readonly string $selectedWeek,
        public readonly Carbon $fromDate,
        public readonly Carbon $toDate,
    ) {
    }
}