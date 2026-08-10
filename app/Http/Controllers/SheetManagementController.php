<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SheetManagementController extends Controller
{
    private const SESSION_KEY = 'wood_sheets';
    private const COUNTER_KEY = 'wood_sheets_next_id';

    public function index()
    {
        $sheets = $this->getSheets();

        return view('user.sheet-management.index', [
            'sheets' => $sheets,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'        => 'nullable|string|max:100',
            'length_mm'    => 'required|numeric|min:1',
            'width_mm'     => 'required|numeric|min:1',
            'thickness_mm' => 'required|numeric|min:0.1',
        ]);

        $sheets = $this->getSheets();

        $newSheet = [
            'id'           => $this->nextId(),
            'code'         => $this->generateCode(),
            'label'        => $data['label'] ?: null,
            'length_mm'    => (float) $data['length_mm'],
            'width_mm'     => (float) $data['width_mm'],
            'thickness_mm' => (float) $data['thickness_mm'],
            'status'       => 'in_stock',
            'parent_id'    => null,
            'created_at'   => now()->toDateTimeString(),
        ];

        $sheets[] = $newSheet;
        $this->saveSheets($sheets);

        return response()->json([
            'sheet'  => $newSheet,
            'sheets' => $sheets,
        ]);
    }

    public function cut(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'cut_length' => 'required|numeric|min:0.1',
            'cut_width'  => 'required|numeric|min:0.1',
        ]);

        $sheets = $this->getSheets();
        $index = null;

        foreach ($sheets as $i => $sheet) {
            if ($sheet['id'] === $id && $sheet['status'] === 'in_stock') {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return response()->json(['message' => 'Sheet not found or already used.'], 404);
        }

        $original = $sheets[$index];
        $cutLength = (float) $data['cut_length'];
        $cutWidth  = (float) $data['cut_width'];

        if ($cutLength > (float) $original['length_mm'] || $cutWidth > (float) $original['width_mm']) {
            return response()->json(['message' => 'Cut dimensions exceed sheet size.'], 422);
        }

        array_splice($sheets, $index, 1);

        // Step 1: split along length -> cut slice (full width) + remainder A (leftover length strip)
        $lengthLeftover = $original['length_mm'] - $cutLength;
        $remainderA = null;

        if ($lengthLeftover > 0.01) {
            $remainderA = $this->makeSheet(
                $lengthLeftover, $original['width_mm'], $original['thickness_mm'],
                $original['label'], 'in_stock', $original['id']
            );
            $sheets[] = $remainderA;
        }

        // Step 2: split the slice along width -> final cut piece + remainder B (leftover width strip)
        $widthLeftover = $original['width_mm'] - $cutWidth;
        $remainderB = null;

        if ($widthLeftover > 0.01) {
            $remainderB = $this->makeSheet(
                $cutLength, $widthLeftover, $original['thickness_mm'],
                $original['label'], 'in_stock', $original['id']
            );
            $sheets[] = $remainderB;
        }

        $cutPiece = $this->makeSheet(
            $cutLength, $cutWidth, $original['thickness_mm'],
            $original['label'], 'used', $original['id']
        );
        $sheets[] = $cutPiece;

        $this->saveSheets($sheets);

        return response()->json([
            'original_id' => $original['id'],
            'cut_piece'   => $cutPiece,
            'remainders'  => array_values(array_filter([$remainderA, $remainderB])),
            'sheets'      => $sheets,
        ]);
    }

    private function makeSheet(float $length, float $width, float $thickness, ?string $label, string $status, int $parentId): array
    {
        return [
            'id'           => $this->nextId(),
            'code'         => $this->generateCode(),
            'label'        => $label,
            'length_mm'    => $length,
            'width_mm'     => $width,
            'thickness_mm' => $thickness,
            'status'       => $status,
            'parent_id'    => $parentId,
            'created_at'   => now()->toDateTimeString(),
        ];
    }

    public function destroy(int $id): JsonResponse
    {
        $sheets = $this->getSheets();
        $sheets = array_values(array_filter($sheets, fn ($s) => $s['id'] !== $id));
        $this->saveSheets($sheets);

        return response()->json(['sheets' => $sheets]);
    }

    private function getSheets(): array
    {
        if (!session()->has(self::SESSION_KEY)) {
            $this->seed();
        }

        return session(self::SESSION_KEY, []);
    }

    private function saveSheets(array $sheets): void
    {
        session([self::SESSION_KEY => $sheets]);
    }

    private function nextId(): int
    {
        $next = session(self::COUNTER_KEY, 1);
        session([self::COUNTER_KEY => $next + 1]);
        return $next;
    }

    private function generateCode(): string
    {
        return 'SHT-' . strtoupper(Str::padLeft((string) random_int(0, 9999), 4, '0'));
    }

    private function seed(): void
    {
        $sheets = [
            [
                'id' => 1, 'code' => 'SHT-0001', 'label' => 'Birke Multiplex',
                'length_mm' => 2500, 'width_mm' => 1250, 'thickness_mm' => 18,
                'status' => 'in_stock', 'parent_id' => null, 'created_at' => now()->toDateTimeString(),
            ],
            [
                'id' => 2, 'code' => 'SHT-0002', 'label' => 'MDF Roh',
                'length_mm' => 2000, 'width_mm' => 1000, 'thickness_mm' => 16,
                'status' => 'in_stock', 'parent_id' => null, 'created_at' => now()->toDateTimeString(),
            ],
        ];

        session([self::SESSION_KEY => $sheets, self::COUNTER_KEY => 3]);
    }
}