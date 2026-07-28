<?php

namespace App\Http\Controllers;

use App\Models\Lager;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\MaterialSheet;
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
            'on_hold_quantity' => $m->on_hold_quantity,
            'order_quantity' => $m->order_quantity,
            'quantity' => $m->quantity,
            'available_total' => $m->available_total,
            'shelf' => $m->tablar,
            'threshold' => $m->threshold,
            'type' => $m->type,
            'unit' => $m->unit,
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

            if ($threshold > 0 && $material->available_total <= $threshold) {
                $alreadyExists = Notification::where('type', 'low_stock')
                    ->where('message', 'like', '%'.$material->name.'%')
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (! $alreadyExists) {
                    new_notification(
                        type: 'low_stock',
                        message: "{$material->name} ist im Lager fast leer. Bitte nachbestellen.",
                        url: route('admin.tablar.show', ['lager_id' => $lager_id, 'id' => $material->id]),
                    );
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

            if ($threshold > 0 && $material->available_total > $threshold) {
                Notification::where('type', 'low_stock')
                    ->where('message', 'like', '%'.$material->name.'%')
                    ->whereDate('created_at', now()->toDateString())
                    ->delete();
            }

            return $material->fresh();
        });

        return response()->json(['success' => true, 'new_quantity' => $material->quantity]);
    }

    public function reserve(Request $request, int $lager_id)
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

            if ($material->quantity < $data['quantity']) {
                abort(400, 'Nicht genügend Bestand für die Reservierung');
            }

            $material->decrement('quantity', $data['quantity']);
            $material->increment('on_hold_quantity', $data['quantity']);

            // NEW: log the reservation for admin visibility
            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => $data['quantity'],
                'consumption_type' => 'reserve',
                'consumption_time' => now(),
            ]);

            return $material->fresh();
        });

        return response()->json(['success' => true, 'new_quantity' => $material->quantity, 'on_hold_quantity' => $material->on_hold_quantity]);
    }

    /**
     * Settle a reservation: part goes back to available stock,
     * the rest is permanently consumed from the on-hold amount.
     */
    public function settleReservation(Request $request, int $lager_id)
    {
        Lager::findOrFail($lager_id);

        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'return_quantity' => 'required|integer|min:0',
        ]);

        $material = DB::transaction(function () use ($data, $lager_id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($data['material_id']);

            $onHold = $material->on_hold_quantity;

            if ($data['return_quantity'] > $onHold) {
                abort(400, 'Menge übersteigt die reservierte Menge');
            }

            $returned = $data['return_quantity'];
            $consumed = $onHold - $returned;

            // Whole reservation batch is closed out
            $material->decrement('on_hold_quantity', $onHold);

            if ($returned > 0) {
                $material->increment('quantity', $returned);

                MaterialConsumption::create([
                    'material_id' => $material->id,
                    'quantity' => $returned,
                    'consumption_type' => 'return',
                    'consumption_time' => now(),
                ]);
            }

            if ($consumed > 0) {
                MaterialConsumption::create([
                    'material_id' => $material->id,
                    'quantity' => $consumed,
                    'consumption_type' => 'use',
                    'consumption_time' => now(),
                ]);
            }

            $threshold = (int) ($material->threshold ?? 0);

            if ($returned > 0 && $threshold > 0 && $material->available_total > $threshold) {
                Notification::where('type', 'low_stock')
                    ->where('message', 'like', '%'.$material->name.'%')
                    ->whereDate('created_at', now()->toDateString())
                    ->delete();
            }

            return $material->fresh();
        });

        return response()->json([
            'success' => true,
            'new_quantity' => $material->quantity,
            'on_hold_quantity' => $material->on_hold_quantity,
        ]);
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

    public function sheetOptions(int $lager_id, int $material_id)
    {
        $material = Material::where('lager_id', $lager_id)->findOrFail($material_id);

        if (!$material->isSheetMaterial()) {
            abort(400, 'Dieses Material gehört nicht zum Holzlager.');
        }

        $cutSheets = $material->sheets()
            ->inStock()
            ->cutSheets()
            ->orderByDesc('created_at')
            ->get();

        $referenceFullSheet = $material->sheets()->inStock()->fullSheets()->first();

        $options = [];

        if ($material->quantity > 0) {
            $options[] = [
                'type' => 'full',
                'sheet_id' => null, // backend picks any available full-sheet row when cutting
                'label' => 'Volle Platte',
                'quantity' => $material->quantity,
                'length_mm' => $referenceFullSheet?->length_mm,
                'width_mm' => $referenceFullSheet?->width_mm,
                'thickness_mm' => $referenceFullSheet?->thickness_mm,
            ];
        }

        foreach ($cutSheets as $s) {
            $options[] = [
                'type' => 'cut',
                'sheet_id' => $s->id,
                'label' => $s->code,
                'quantity' => 1,
                'length_mm' => $s->length_mm,
                'width_mm' => $s->width_mm,
                'thickness_mm' => $s->thickness_mm,
            ];
        }

        return response()->json([
            'material_id' => $material->id,
            'options' => $options,
        ]);
    }

    // ─── CUT A SHEET (full or already-cut) ─────────────────────────────────────

    public function cutSheet(Request $request, int $lager_id)
    {
        Lager::findOrFail($lager_id);

        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'sheet_id' => 'nullable|exists:material_sheets,id', // null = take from full-sheet bucket
            'cut_length' => 'required|numeric|min:0.1',
            'cut_width' => 'required|numeric|min:0.1',
        ]);

        $result = DB::transaction(function () use ($data, $lager_id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($data['material_id']);

            if (!$material->isSheetMaterial()) {
                abort(400, 'Dieses Material gehört nicht zum Holzlager.');
            }

            if ($data['sheet_id']) {
                $source = MaterialSheet::where('material_id', $material->id)
                    ->where('status', 'in_stock')
                    ->lockForUpdate()
                    ->findOrFail($data['sheet_id']);
            } else {
                $source = MaterialSheet::where('material_id', $material->id)
                    ->inStock()
                    ->fullSheets()
                    ->lockForUpdate()
                    ->first();

                if (!$source) {
                    abort(400, 'Keine volle Platte mehr auf Lager.');
                }
            }

            $cutLength = (float) $data['cut_length'];
            $cutWidth = (float) $data['cut_width'];

            if ($cutLength > (float) $source->length_mm || $cutWidth > (float) $source->width_mm) {
                abort(422, 'Zuschnitt übersteigt die Größe der Platte.');
            }

            $wasFullSheet = $source->isFullSheet();

            $source->status = 'used';
            $source->save();

            // Step 1: split along length -> leftover strip (full width)
            $lengthLeftover = $source->length_mm - $cutLength;
            $remainderA = null;

            if ($lengthLeftover > 0.01) {
                $remainderA = MaterialSheet::create([
                    'material_id' => $material->id,
                    'code' => MaterialSheet::generateCode(),
                    'length_mm' => $lengthLeftover,
                    'width_mm' => $source->width_mm,
                    'thickness_mm' => $source->thickness_mm,
                    'status' => 'in_stock',
                    'parent_sheet_id' => $source->id,
                ]);
            }

            // Step 2: split the slice along width -> leftover strip
            $widthLeftover = $source->width_mm - $cutWidth;
            $remainderB = null;

            if ($widthLeftover > 0.01) {
                $remainderB = MaterialSheet::create([
                    'material_id' => $material->id,
                    'code' => MaterialSheet::generateCode(),
                    'length_mm' => $cutLength,
                    'width_mm' => $widthLeftover,
                    'thickness_mm' => $source->thickness_mm,
                    'status' => 'in_stock',
                    'parent_sheet_id' => $source->id,
                ]);
            }

            $cutPiece = MaterialSheet::create([
                'material_id' => $material->id,
                'code' => MaterialSheet::generateCode(),
                'length_mm' => $cutLength,
                'width_mm' => $cutWidth,
                'thickness_mm' => $source->thickness_mm,
                'status' => 'used',
                'parent_sheet_id' => $source->id,
            ]);

            // A full sheet that gets touched at all stops being "full" -> quantity -1.
            // Cutting an already-cut sheet further never touches quantity (never counted there).
            if ($wasFullSheet) {
                $material->decrement('quantity', 1);
            }

            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => 1,
                'consumption_type' => 'use',
                'consumption_time' => now(),
            ]);

            return [
                'cut_piece' => $cutPiece,
                'remainders' => array_values(array_filter([$remainderA, $remainderB])),
                'new_quantity' => $material->fresh()->quantity,
            ];
        });

        return response()->json($result);
    }
}
