<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFraesdataRequest;
use App\Models\Fraesdata;
use App\Models\Material;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class FraesdataController extends Controller
{
    public function create(Request $request, Project $project)
    {
        $positionId = $request->query('position_id');
        $bauteilId = $request->query('bauteil_id');

        abort_if((bool) $positionId === (bool) $bauteilId, 404);

        $position = $positionId
            ? $project->positions()->findOrFail($positionId)
            : null;
        $bauteil = $bauteilId
            ? $project->bauteile()->findOrFail($bauteilId)
            : null;

        $relatedPositionId = $request->query('related_position_id');
        if ($relatedPositionId) {
            abort_unless($bauteil, 404);
            $project->positions()->findOrFail($relatedPositionId);
        }

        $positions = $bauteil
            ? $project->positions()->orderBy('name')->get()
            : collect();

        return view('admin.fraesdata.create', compact(
            'project',
            'position',
            'bauteil',
            'positions',
            'relatedPositionId',
        ));
    }

    public function store(StoreFraesdataRequest $request, Project $project)
    {
        $validated = $request->validated();
        $documentPath = $request->file('document')->store('fraesdata', 'local');

        if (! $documentPath) {
            throw new RuntimeException('Unable to store the CNC design document.');
        }

        try {
            DB::transaction(function () use ($validated, $documentPath, $project, $request) {
                $fraesdata = Fraesdata::create([
                    'project_id' => $project->id,
                    'position_id' => $validated['position_id'] ?? null,
                    'bauteil_id' => $validated['bauteil_id'] ?? null,
                    'related_position_id' => $validated['related_position_id'] ?? null,
                    'name' => $validated['name'],
                    'document_path' => $documentPath,
                    'uploaded_by' => $request->user()->id,
                ]);

                foreach ($validated['materials'] ?? [] as $material) {
                    $fraesdata->tools()->create($material);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($documentPath);
            throw $exception;
        }

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', __('fraesdata.upload.success'));
    }

    public function searchMaterials(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);
        $term = trim($validated['q']);

        if ($term === '') {
            return response()->json(['data' => []]);
        }

        $materials = Material::query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'code', 'is_werkzeug']);

        return response()->json([
            'data' => $materials,
        ]);
    }
}
