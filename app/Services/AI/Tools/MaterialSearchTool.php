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
                'name' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ],
        ];
    }

    public function isAuthorized(?Authenticatable $user): bool
    {
        return $user !== null; // must be logged in, any role
    }

    public function handle(array $arguments): array
    {
        $query = Material::query();

        if (!empty($arguments['name'])) {
            $query->where('name', 'LIKE', '%'.addcslashes($arguments['name'], '%_').'%');
        }
        if (!empty($arguments['description'])) {
            $query->where('description', 'LIKE', '%'.addcslashes($arguments['description'], '%_').'%');
        }

        $materials = $query->limit(10)->get();

        return [
            'total_matches' => $materials->count(),
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
                'lager_id' => $m->lager_id,
                'is_werkzeug' => $m->is_werkzeug,
                'is_active' => $m->is_active,
            ])->all(),
        ];
    }
}