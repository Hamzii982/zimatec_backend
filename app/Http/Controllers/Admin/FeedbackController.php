<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        // -----------------------------
        // Base Query
        // -----------------------------
        $query = Feedback::query();

        // -----------------------------
        // Filters
        // -----------------------------
        if ($request->filled('machine')) {
            $query->whereIn('machine', (array) $request->machine);
        }

        if ($request->filled('ai_only')) {
            $query->where('ai_solution', 'ja');
        }

        if ($request->filled('sentiment')) {
            if ($request->sentiment === 'frustrated') {
                $query->where('description', 'like', '%nicht%')
                    ->orWhere('description', 'like', '%problem%');
            }
        }

        // -----------------------------
        // Main dataset
        // -----------------------------
        $feedbacks = $query->latest()->get();

        // -----------------------------
        // COMMAND CENTER METRICS
        // -----------------------------

        // AI Readiness Ratio
        $totals = Feedback::selectRaw("
            SUM(CASE WHEN ai_solution = 'ja' THEN 1 ELSE 0 END) as ready,
            SUM(CASE WHEN ai_solution = 'naja' THEN 1 ELSE 0 END) as partial,
            SUM(CASE WHEN ai_solution = 'nein' THEN 1 ELSE 0 END) as not_ready,
            SUM(CASE WHEN ai_solution IS NULL THEN 1 ELSE 0 END) as unknown
        ")->first();

        $aiStats = [
            'ready' => (int) $totals->ready,
            'partial' => (int) $totals->partial,
            'not_ready' => (int) $totals->not_ready,
            'unknown' => (int) $totals->unknown,
        ];

        // Machine/Process Feedback Heatmap 
        $heatmap = Feedback::select('machine', DB::raw('COUNT(*) as count'))
            ->groupBy('machine')
            ->orderByDesc('count')
            ->get();

        // Priority distribution
        $priority = Feedback::select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority');

        return view('admin.feedback.index', [
            'feedbacks' => $feedbacks,
            'aiStats' => $aiStats,
            'heatmap' => $heatmap,
            'priority' => $priority,
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:fehler,vorschlag,anleitung',
            'machine' => 'required|string|max:255',
            'description' => 'required|string',
            'ai_solution' => 'nullable|string',
            'priority' => 'required|in:niedrig,mittel,hoch',
            'name' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $path = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('feedback', 'public');
        }

        $feedback = Feedback::create([
            'type' => $validated['type'],
            'machine' => $validated['machine'],
            'description' => $validated['description'],
            'ai_solution' => $validated['ai_solution'] ?? null,
            'priority' => $validated['priority'],
            'name' => $validated['name'] ?? null,
            'attachment' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback erfolgreich abgegen',
            'data' => $feedback
        ], 201);
    }
}
