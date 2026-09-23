<?php

namespace App\Repositories;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Query logic for the legacy project/procedure/bauteil machine-log
 * browsing screen. Extracted from the HandleMachineLogs trait so it can
 * be constructor-injected instead of mixed into the controller.
 */
class MachineLogRepository
{
    public function filter(Request $request): array
    {
        $query = Project::with(['procedures.processes', 'processes', 'bauteile.processes'])
            ->where(function ($q) {
                $q->whereHas('processes')
                    ->orWhereHas('procedures.processes')
                    ->orWhereHas('bauteile.processes');
            });

        if ($request->filled('project_id')) {
            $query->where('id', $request->project_id);
        }

        // ✅ Filter by calendar week
        if ($request->filled('week')) {
            // week format = "2025-W04"
            [$year, $week] = explode('-W', $request->week);

            $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
            $endOfWeek = Carbon::now()->setISODate($year, $week)->endOfWeek();

            $query->where(function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereHas('processes', function ($q2) use ($startOfWeek, $endOfWeek) {
                    $q2->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                        ->orWhereBetween('end_time', [$startOfWeek, $endOfWeek]);
                })
                    ->orWhereHas('procedures.processes', function ($q3) use ($startOfWeek, $endOfWeek) {
                        $q3->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                            ->orWhereBetween('end_time', [$startOfWeek, $endOfWeek]);
                    })
                    ->orWhereHas('bauteile.processes', function ($q4) use ($startOfWeek, $endOfWeek) {
                        $q4->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                            ->orWhereBetween('end_time', [$startOfWeek, $endOfWeek]);
                    });
            });
        }

        // ✅ Filter by single day
        elseif ($request->filled('day')) {
            $day = Carbon::parse($request->day)->toDateString();

            $query = Project::with([
                'processes' => function ($q) use ($day) {
                    $q->whereDate('start_time', $day)
                        ->orWhereDate('end_time', $day);
                },
                'procedures.processes' => function ($q) use ($day) {
                    $q->whereDate('start_time', $day)
                        ->orWhereDate('end_time', $day);
                },
                'bauteile.processes' => function ($q) use ($day) {
                    $q->whereDate('start_time', $day)
                        ->orWhereDate('end_time', $day);
                },
            ])
                ->where(function ($q) use ($day) {
                    $q->whereHas('processes', function ($q2) use ($day) {
                        $q2->whereDate('start_time', $day)
                            ->orWhereDate('end_time', $day);
                    })
                        ->orWhereHas('procedures.processes', function ($q3) use ($day) {
                            $q3->whereDate('start_time', $day)
                                ->orWhereDate('end_time', $day);
                        })
                        ->orWhereHas('bauteile.processes', function ($q4) use ($day) {
                            $q4->whereDate('start_time', $day)
                                ->orWhereDate('end_time', $day);
                        });
                });
        }

        // Paginated projects
        $projects = $query->paginate(5)->withQueryString();

        // Filter out empty relations
        $projects->each(function ($project) {
            $project->bauteile = $project->bauteile->filter(fn ($b) => $b->processes->isNotEmpty());
            $project->procedures = $project->procedures->filter(fn ($p) => $p->processes->isNotEmpty());
        });

        $allProjects = Project::orderBy('project_name')
            ->where(function ($q) {
                $q->whereHas('processes')
                    ->orWhereHas('procedures.processes')
                    ->orWhereHas('bauteile.processes');
            })
            ->get();

        return compact('projects', 'allProjects');
    }
}