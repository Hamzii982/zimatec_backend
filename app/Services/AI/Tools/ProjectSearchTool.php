<?php
namespace App\Services\AI\Tools;

use App\Models\Project;
use App\Services\AI\Contracts\AiToolContract;
use Illuminate\Contracts\Auth\Authenticatable;

class ProjectSearchTool implements AiToolContract
{
    private const SORT_COLUMNS = [
        'created_at' => 'created_at',
        'end_time' => 'end_time',
        'start_time' => 'start_time',
    ];

    private const STATUSES = ['neue', 'in_arbeit', 'abgeschlossen', 'storniert'];

    public function name(): string
    {
        return 'search_projects';
    }

    public function description(): string
    {
        return 'Search projects/orders by name, order number, status, position, date range, or deadline. Returns a curated summary, not raw records.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'project_name' => ['type' => 'string'],
                'auftragsnummer_zf' => ['type' => 'string'],
                'auftragsnummer_zt' => ['type' => 'string'],
                'start_date' => ['type' => 'string', 'format' => 'date'],
                'end_date' => ['type' => 'string', 'format' => 'date'],
                'time_scope' => ['type' => 'string', 'enum' => ['all', 'past', 'future_deadlines']],
                'sort_by' => ['type' => 'string', 'enum' => array_keys(self::SORT_COLUMNS)],
                'sort_direction' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'project_status' => ['type' => 'string', 'enum' => self::STATUSES],
                'project_positions' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];
    }

    public function isAuthorized(?Authenticatable $user): bool
    {
        // return $user !== null;
        return true;
    }

    public function handle(array $arguments): array
    {
        $query = Project::query();

        if (!empty($arguments['project_name'])) {
            $query->where('project_name', 'LIKE', '%'.$this->escapeLike($arguments['project_name']).'%');
        }
        if (!empty($arguments['auftragsnummer_zf'])) {
            $query->where('auftragsnummer_zf', $arguments['auftragsnummer_zf']);
        }
        if (!empty($arguments['auftragsnummer_zt'])) {
            $query->where('auftragsnummer_zt', $arguments['auftragsnummer_zt']);
        }
        if (!empty($arguments['start_date'])) {
            $query->where('start_time', '>=', $arguments['start_date']);
        }
        if (!empty($arguments['end_date'])) {
            $query->where('end_time', '<=', $arguments['end_date']);
        }

        // Never trust the schema enum alone — re-validate before touching the query.
        if (!empty($arguments['project_status']) && in_array($arguments['project_status'], self::STATUSES, true)) {
            $query->whereHas('status', fn ($q) => $q->where('name', $arguments['project_status']));
        }

        if (!empty($arguments['project_positions']) && is_array($arguments['project_positions'])) {
            $safePositions = array_values(array_filter($arguments['project_positions'], 'is_string'));
            if ($safePositions) {
                $query->whereHas('positions', fn ($q) => $q->whereIn('name', $safePositions));
            }
        }

        if (($arguments['time_scope'] ?? null) === 'future_deadlines') {
            $query->where('end_time', '>=', now());
        } elseif (($arguments['time_scope'] ?? null) === 'past') {
            $query->where('end_time', '<', now());
        }

        // Column name never comes straight from model output — mapped through a whitelist.
        $sortColumn = self::SORT_COLUMNS[$arguments['sort_by'] ?? 'created_at'] ?? 'created_at';
        $sortDirection = ($arguments['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $total = (clone $query)->count();

        $projects = $query->with(['status', 'positions'])->limit(10)->get();

        return [
            'total_matches' => $total,
            'showing' => $projects->count(),
            'projects' => $projects->map(fn (Project $p) => [
                'project_name' => $p->project_name,
                'auftragsnummer_zf' => $p->auftragsnummer_zf,
                'auftragsnummer_zt' => $p->auftragsnummer_zt,
                'status' => $p->status?->name,
                'start_time' => $p->start_time,
                'end_time' => $p->end_time,
                'positions' => $p->positions->pluck('name'),
            ])->all(),
        ];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_');
    }
}