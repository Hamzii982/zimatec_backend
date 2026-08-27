<?php
// app/Services/AI/Tools/MaterialSearchTool.php
namespace App\Services\AI\Tools;

use App\Models\Material;
use App\Services\AI\Contracts\AiToolContract;
use Illuminate\Contracts\Auth\Authenticatable;

class MaterialSearchTool implements AiToolContract
{
    public function name(): string
    {
        return 'search_materials';
    }

    public function description(): string
    {
        return 'Search available printing materials by name or type, including stock status.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'keyword' => [
                    'type' => 'string',
                    'description' => 'Free-text search term matched against material name or description.',
                ],
                'type' => ['type' => 'string'],
                'lager_name' => [
                    'type' => 'string',
                    'description' => 'Name of the storage location (Lager), e.g. "Halle A". Partial match allowed.',
                ],
                'tablar' => [
                    'type' => 'string',
                    'description' => 'Specific shelf/rack (Tablar) identifier to filter by.',
                ],
                'is_werkzeug' => [
                    'type' => 'boolean',
                    'description' => 'If true, only tools (Werkzeug); if false, only materials; omit for both.',
                ],
                'is_active' => ['type' => 'boolean'],
                'quantity_below_threshold' => [
                    'type' => 'boolean',
                    'description' => 'If true, only return materials where quantity < threshold',
                ],
                'sort_by' => [
                    'type' => 'string',
                    'enum' => ['quantity', 'threshold', 'name', 'created_at'],
                ],
                'sort_direction' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'limit' => ['type' => 'integer', 'description' => 'Max results, default 10, max 50'],
            ],
        ];
    }

    public function isAuthorized(?Authenticatable $user): bool
    {
        // return $user !== null;
        return true;
    }

    public function handle(array $arguments, ?Authenticatable $user = null): array
    {
        $query = Material::query();

        if (!empty($arguments['keyword'])) {
            $kw = addcslashes($arguments['keyword'], '%_');
            $query->where(function ($q) use ($arguments, $kw) {
                $q->where('name', 'LIKE', '%'.$kw.'%')
                  ->orWhere('description', 'LIKE', '%'.$kw.'%');
            });
        }
        if (!empty($arguments['type'])) {
            $query->where('type', $arguments['type']);
        }
        // Resolve lager NAME -> id server-side. LLM never sees/needs the id.
        if (!empty($arguments['lager_name'])) {
            $query->whereHas('lager', function ($q) use ($arguments) {
                $q->where('name', 'LIKE', '%'.addcslashes($arguments['lager_name'], '%_').'%');
            });
        }

        if (!empty($arguments['tablar'])) {
            $query->where('tablar', 'LIKE', '%'.addcslashes($arguments['tablar'], '%_').'%');
        }

        if (isset($arguments['is_werkzeug'])) {
            $query->where('is_werkzeug', $arguments['is_werkzeug']);
        }
        if (isset($arguments['is_active'])) {
            $query->where('is_active', $arguments['is_active']);
        }
        if (!empty($arguments['quantity_below_threshold'])) {
            $query->whereColumn('quantity', '<', 'threshold');
        }
        if (!empty($arguments['sort_by'])) {
            $query->orderBy($arguments['sort_by'], $arguments['sort_direction'] ?? 'asc');
        }

        $limit = min((int) ($arguments['limit'] ?? 10), 50);
        $totalMatches = (clone $query)->count(); // count BEFORE limit is applied

        $materials = $query->limit($limit)->get();

        return [
            'total_matches' => $totalMatches,
            'returned' => $materials->count(),
            'materials' => $materials->map(fn ($m) => [
                'name' => $m->name,
                'Artikelnummer' => $m->code,
                'description' => $m->description,
                'quantity' => $m->quantity,
                'on_hold_quantity' => $m->on_hold_quantity,
                'order_quantity' => $m->order_quantity,
                'tablar' => $m->tablar,
                'threshold' => $m->threshold,
                'type' => $m->type,
                'unit' => $m->unit,
                'image' => $m->image,
                'order_status' => $m->order_status,
                'lager' => [
                    'id' => $m->lager_id,
                    'name' => $m->lager?->name,
                ],
                'is_werkzeug' => $m->is_werkzeug,
                'is_active' => $m->is_active,
            ])->all(),
        ];
    }
}