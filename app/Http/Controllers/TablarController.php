<?php

namespace App\Http\Controllers;

use App\Models\Lager;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function App\Helpers\new_notification;

class TablarController extends Controller
{
    public function lagerSelect()
    {
        $lagers = Lager::withCount('materials')->orderBy('name')->get();

        return view('user.tablar.lager-select', compact('lagers'));
    }

    public function index(int $lager_id)
    {
        $lager = Lager::findOrFail($lager_id);

        $materials = Material::where('lager_id', $lager_id)
            ->orderBy('tablar')
            ->orderBy('name')
            ->get();

        $flatList = $materials->map(fn ($m) => [
            'id' => $m->id,
            'code' => $m->code,
            'name' => $m->name,
            'description' => $m->description,
            'quantity' => $m->quantity,
            'shelf' => $m->tablar,
            'threshold' => $m->threshold,
            'type' => $m->type,
            'image' => $m->image,
            'order_status' => $m->order_status,
            'status' => $m->status,
        ])->values();

        $shelves = $materials->pluck('tablar')->unique()->sort()->values();

        $statusTranslations = [
            'notified' => 'Bedarf gemeldet',
            'ordered' => 'Bestellt',
            'blocked' => 'Blockiert',
            'delivered' => 'Geliefert',
        ];

        return view('user.tablar.index', compact('flatList', 'shelves', 'statusTranslations', 'lager'));
    }

    /**
     * Record material consumption (real-time, concurrency-safe)
     */
    public function consume(Request $request, int $lager_id)
    {
        $lager = Lager::findOrFail($lager_id);

        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $material = DB::transaction(function () use ($data, $lager_id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($data['material_id']);

            if ($material->quantity < $data['quantity']) {
                abort(400, 'Nicht genügend Bestand');
            }

            $material->decrement('quantity', $data['quantity']);

            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => $data['quantity'],
                'consumption_type' => 'use',
                'consumption_time' => now(),
            ]);

            $threshold = (int) ($material->threshold ?? 0);

            if ($threshold > 0 && $material->quantity <= $threshold) {
                $alreadyExists = Notification::where('type', 'low_stock')
                    ->where('message', 'like', '%'.$material->name.'%')
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (! $alreadyExists) {
                    Notification::create([
                        'user_id' => null,
                        'type' => 'low_stock',
                        'message' => "{$material->name} ist im Lager fast leer. Bitte nachbestellen.",
                        'url' => route('admin.tablar.show', ['lager_id' => $lager_id, 'id' => $material->id]),
                    ]);
                }
            }

            return $material->fresh();
        });

        return response()->json(['success' => true, 'new_quantity' => $material->quantity]);
    }

    /**
     * Record material return (real-time, concurrency-safe)
     */
    public function return(Request $request, int $lager_id)
    {
        Lager::findOrFail($lager_id);

        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $material = DB::transaction(function () use ($data, $lager_id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($data['material_id']);

            $material->increment('quantity', $data['quantity']);

            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => $data['quantity'],
                'consumption_type' => 'return',
                'consumption_time' => now(),
            ]);

            $threshold = (int) ($material->threshold ?? 0);

            if ($threshold > 0 && $material->quantity > $threshold) {
                Notification::where('type', 'low_stock')
                    ->where('message', 'like', '%'.$material->name.'%')
                    ->whereDate('created_at', now()->toDateString())
                    ->delete();
            }

            return $material->fresh();
        });

        return response()->json(['success' => true, 'new_quantity' => $material->quantity]);
    }

    // ─── ORDER REQUEST ───────────────────────────────────────────────────────

    public function orderRequest(int $lager_id, $materialId)
    {
        $material = Material::where('lager_id', $lager_id)->findOrFail($materialId);

        if ($material->order_status !== null) {
            return response()->json(['message' => 'Bestellung bereits angefragt.'], 400);
        }

        $material->order_status = 'notified';
        $material->save();

        // 2. Check if a notification for this specific order request already exists today
        $alreadyExists = Notification::where('type', 'order_request')
            ->where('message', 'like', '%'.$material->name.'%')
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        // 3. Create the notification if it doesn't exist
        if (! $alreadyExists) {

            new_notification(
                type:    'order_request',
                message: "Bestellungsanfrage für {$material->name} im Lager wurde gestellt.",
                url:     route('admin.tablar.show', ['lager_id' => $lager_id, 'id' => $material->id]),
            );
        }

        return response()->json([
            'message' => 'Bestellung angefragt.',
            'order_status' => $material->order_status,
        ]);
    }
}
