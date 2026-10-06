<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bauteil;
use App\Models\ProjectStatus;
use App\Models\Supplier;
use App\Models\SupplierOffer;
use App\Models\SupplierProject;
use Illuminate\Http\Request;

class SupplierProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierProject::with(['offer.supplier', 'offer.bauteil', 'status']);

        // 🔹 Filter: Project Status
        if ($request->filled('project_status_id')) {
            $query->where('project_status_id', $request->project_status_id);
        }

        // 🔹 Filter: Supplier
        if ($request->filled('supplier_id')) {
            $query->whereHas('offer', function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }

        // 🔹 Filter: Bauteil
        if ($request->filled('bauteil_id')) {
            $query->whereHas('offer', function ($q) use ($request) {
                $q->where('bauteil_id', $request->bauteil_id);
            });
        }

        // 🔹 Filter: Date Range
        if ($request->filled('start_date_from') && $request->filled('start_date_to')) {
            $query->whereBetween('start_date', [$request->start_date_from, $request->start_date_to]);
        }

        if ($request->filled('end_date_from') && $request->filled('end_date_to')) {
            $query->whereBetween('end_date', [$request->end_date_from, $request->end_date_to]);
        }

        // 🔹 Filter: Past Projects (End date < today)
        if ($request->filled('past_projects') && $request->past_projects == '1') {
            $query->where('end_date', '<', now());
        }

        // 🔹 Filter: Total Price (min & max)
        if ($request->filled('price_min') || $request->filled('price_max')) {
            $projects = $query->get()->filter(function ($p) use ($request) {
                $total = $p->gesamtpreis;

                return (! $request->filled('price_min') || $total >= $request->price_min)
                    && (! $request->filled('price_max') || $total <= $request->price_max);
            });
        } else {
            $projects = $query->paginate(10);
        }

        // Dropdown data
        $statuses = ProjectStatus::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $bauteile = Bauteil::orderBy('name')->get();

        return view('admin.projects.projects.index', compact('projects', 'statuses', 'suppliers', 'bauteile'));
    }

    public function create()
    {
        $offers = SupplierOffer::with('supplier')->get();
        $statuses = ProjectStatus::all();

        return view('admin.projects.projects.create', compact('offers', 'statuses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'supplier_offer_id' => 'required|exists:supplier_offers,id',
            'project_status_id' => 'required|exists:project_statuses,id',
            'start_date' => 'nullable|date',
            'checkup_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'extra_note' => 'nullable|string',
            'additional_expense' => 'nullable|numeric',
        ]);

        SupplierProject::create($request->all());

        return redirect()->route('admin.projects.projects.index')
            ->with('success', 'Lieferantenprojekt erfolgreich erstellt.');
    }

    public function show(SupplierProject $project)
    {
        $project->load(['offer.supplier', 'status']);

        return view('admin.projects.projects.show', compact('project'));
    }

    public function edit(SupplierProject $project)
    {
        $offers = SupplierOffer::with('supplier')->get();
        $statuses = ProjectStatus::all();

        return view('admin.projects.projects.edit', compact('project', 'offers', 'statuses'));
    }

    public function update(Request $request, SupplierProject $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'supplier_offer_id' => 'required|exists:supplier_offers,id',
            'project_status_id' => 'required|exists:project_statuses,id',
            'start_date' => 'nullable|date',
            'checkup_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'extra_note' => 'nullable|string',
            'additional_expense' => 'nullable|numeric',
        ]);

        $project->update($request->all());

        return redirect()->route('admin.projects.projects.index')
            ->with('success', 'Projekt erfolgreich aktualisiert.');
    }

    public function destroy(SupplierProject $project)
    {
        $project->delete();

        return redirect()->route('admin.projects.projects.index')
            ->with('success', 'Projekt gelöscht.');
    }
}
