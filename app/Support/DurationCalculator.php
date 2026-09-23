<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Centralises the duration/pause arithmetic that was previously spread
 * across several private methods on TimeController. Stateless and
 * injectable, so it can be unit tested without touching the database.
 */
class DurationCalculator
{
    public function seconds($start, $end): int
    {
        if (! $start || ! $end) {
            return 0;
        }

        return abs(Carbon::parse($start)->diffInSeconds(Carbon::parse($end)));
    }

    /**
     * Sum of a process's pauses, clamped to the process window, with
     * overlapping/touching pause intervals merged so they aren't double
     * counted.
     */
    public function pauseSeconds($process): int
    {
        $intervals = $process->pauses
            ->map(function ($pause) use ($process) {
                $start = Carbon::parse(max($pause->pause_start, $process->start_time));
                $end = Carbon::parse(min($pause->pause_end ?? $process->end_time, $process->end_time));

                return [$start, $end];
            })
            // Drop pauses that don't actually overlap the process after clamping
            // (guards the same abs()-flip issue as before: a non-overlapping
            // pause must contribute 0, not a positive number).
            ->filter(fn ($interval) => $interval[1]->gt($interval[0]))
            ->sortBy(fn ($interval) => $interval[0]->timestamp)
            ->values();

        $merged = [];

        foreach ($intervals as [$start, $end]) {
            $last = count($merged) - 1;

            if ($last >= 0 && $start->lte($merged[$last][1])) {
                // Overlaps (or touches) the previous interval — extend it
                // instead of counting this pause's duration a second time.
                if ($end->gt($merged[$last][1])) {
                    $merged[$last][1] = $end;
                }
            } else {
                $merged[] = [$start, $end];
            }
        }

        return collect($merged)->sum(fn ($interval) => $interval[0]->diffInSeconds($interval[1], true));
    }

    public function activeSeconds($process): int
    {
        return max(0, $this->seconds($process->start_time, $process->end_time) - $this->pauseSeconds($process));
    }

    public function activeSecondsInRange($process, $rangeStart, $rangeEnd): int
    {
        $rangeStart = Carbon::parse($rangeStart);
        $rangeEnd = Carbon::parse($rangeEnd);

        $total = max(0, $rangeEnd->diffInSeconds($rangeStart, true));

        $paused = $process->pauses->sum(function ($pause) use ($rangeStart, $rangeEnd) {
            $pauseStart = Carbon::parse($pause->pause_start);
            $pauseEnd = Carbon::parse($pause->pause_end ?? $rangeEnd);

            $overlapStart = $pauseStart->greaterThan($rangeStart) ? $pauseStart : $rangeStart;
            $overlapEnd = $pauseEnd->lessThan($rangeEnd) ? $pauseEnd : $rangeEnd;

            // Guard: no overlap at all -> contribute 0, don't let abs() flip a
            // negative gap into a positive pause duration.
            if ($overlapEnd->lessThanOrEqualTo($overlapStart)) {
                return 0;
            }

            return $overlapEnd->diffInSeconds($overlapStart, true);
        });

        return max(0, $total - $paused);
    }

    /**
     * Splits a process into per-calendar-day segments, each with its
     * pause-adjusted active seconds. Used to attribute "Ohne Aufsicht"
     * time to the correct day when a process spans midnight.
     *
     * @return array<int, array{date: string, start: Carbon, end: Carbon, seconds: int}>
     */
    public function processDaySegments($process): array
    {
        $start = Carbon::parse($process->start_time);
        $end = Carbon::parse($process->end_time);

        $segments = [];
        $cursor = $start->copy();

        while ($cursor->lt($end)) {
            $midnight = $cursor->copy()->startOfDay()->addDay();
            $segmentEnd = $midnight->lt($end) ? $midnight : $end->copy();

            $segments[] = [
                'date' => $cursor->toDateString(),
                'start' => $cursor->copy(),
                'end' => $segmentEnd->copy(),
                'seconds' => $this->activeSecondsInRange($process, $cursor, $segmentEnd),
            ];

            $cursor = $segmentEnd;
        }

        return $segments;
    }

    /**
     * Simpler, non-merging pause sum used only by the deprecated
     * machineLogsOld view. Kept separate from pauseSeconds() because it
     * is a different algorithm, not a duplicate of it — collapsing the
     * two would change that view's numbers.
     */
    public function legacyPauseSeconds($process): int
    {
        return $process->pauses->sum(function ($pause) {
            // pause_start / pause_end are already Carbon instances (model casts)
            $start = $pause->pause_start;
            $end = $pause->pause_end ?: now();

            return $start->diffInSeconds($end);
        });
    }

    public function legacyActiveSeconds($process): int
    {
        return max(0, ($process->total_seconds ?? 0) - $this->legacyPauseSeconds($process));
    }

    public function hms(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    public function hmsToSeconds(string $hms): int
    {
        [$h, $m, $s] = array_map('intval', explode(':', $hms));

        return $h * 3600 + $m * 60 + $s;
    }
}