<?php

namespace App\Http\Controllers\Admin\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Stage;
use App\Models\Workflow\Step;
use Illuminate\Http\Request;

class StageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $stages = Stage::with(['steps' => fn ($q) => $q->orderBy('order_index')])
            ->orderBy('order_index')
            ->get();

        return view('admin.workflow.settings', [
            'stages' => $stages,
        ]);
    }

    public function storeStage(Request $request)
    {
        $data = $this->validateStage($request);

        Stage::create($data);

        return redirect()
            ->route('admin.workflow.settings')
            ->with('success', 'Stufe angelegt.');
    }

    public function updateStage(Request $request, Stage $stage)
    {
        $data = $this->validateStage($request, $stage);

        $stage->update($data);

        return redirect()
            ->route('admin.workflow.settings')
            ->with('success', 'Stufe aktualisiert.');
    }

    public function destroyStage(Stage $stage)
    {
        if ($stage->workflowProjects()->exists()) {
            $stage->update(['is_active' => false]);

            return redirect()
                ->route('admin.workflow.settings')
                ->with('success', 'Stufe deaktiviert (Projekte vorhanden).');
        }

        $stage->delete();

        return redirect()
            ->route('admin.workflow.settings')
            ->with('success', 'Stufe gelöscht.');
    }

    public function storeStep(Request $request, Stage $stage)
    {
        $data = $this->validateStep($request);

        $data['stage_id'] = $stage->id;
        $data['order_index'] = $data['order_index']
            ?? (int) (($stage->steps()->max('order_index') ?? 0) + 1);

        Step::create($data);

        return redirect()
            ->route('admin.workflow.settings')
            ->with('success', 'Schritt angelegt.');
    }

    public function updateStep(Request $request, Step $step)
    {
        $data = $this->validateStep($request);

        $step->update($data);

        return redirect()
            ->route('admin.workflow.settings')
            ->with('success', 'Schritt aktualisiert.');
    }

    public function destroyStep(Step $step)
    {
        if ($step->projectSteps()->exists()) {
            $step->update(['is_required' => false]);

            return redirect()
                ->route('admin.workflow.settings')
                ->with('success', 'Schritt deaktiviert (Projektschritte vorhanden).');
        }

        $step->delete();

        return redirect()
            ->route('admin.workflow.settings')
            ->with('success', 'Schritt gelöscht.');
    }

    protected function validateStage(Request $request, ?Stage $stage = null): array
    {
        return $request->validate([
            'key' => ['required', 'string', 'max:50', 'unique:workflow_stages,key,'.($stage?->id ?? 'NULL')],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:100'],
            'order_index' => ['required', 'integer', 'min:0'],
            'required_role' => ['nullable', 'in:admin,user'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    protected function validateStep(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
        ]);
    }
}
