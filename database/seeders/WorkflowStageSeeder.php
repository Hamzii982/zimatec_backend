<?php

namespace Database\Seeders;

use App\Models\Workflow\Stage;
use App\Models\Workflow\Step;
use Illuminate\Database\Seeder;

class WorkflowStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            [
                'key' => 'office',
                'name' => 'Büro',
                'color' => '#0d6efd',
                'icon' => 'bi-building',
                'order_index' => 1,
                'required_role' => null,
                'steps' => [
                    ['name' => 'Kundenanfrage erhalten', 'order_index' => 1, 'is_required' => true],
                    ['name' => 'Auftragsnummer vergeben', 'order_index' => 2, 'is_required' => true],
                    ['name' => 'Kundenanforderungen dokumentiert', 'order_index' => 3, 'is_required' => true],
                ],
            ],
            [
                'key' => 'design',
                'name' => 'Konstruktion',
                'color' => '#6f42c1',
                'icon' => 'bi-rulers',
                'order_index' => 2,
                'required_role' => null,
                'steps' => [
                    ['name' => 'CAD-Modell erstellt', 'order_index' => 1, 'is_required' => true],
                    ['name' => 'Zeichnung freigegeben', 'order_index' => 2, 'is_required' => true],
                    ['name' => 'Stückliste vollständig', 'order_index' => 3, 'is_required' => true],
                ],
            ],
            [
                'key' => 'workshop',
                'name' => 'Werkstatt',
                'color' => '#fd7e14',
                'icon' => 'bi-tools',
                'order_index' => 3,
                'required_role' => null,
                'steps' => [
                    ['name' => 'Material bereitgestellt', 'order_index' => 1, 'is_required' => true],
                    ['name' => 'Fertigung gestartet', 'order_index' => 2, 'is_required' => true],
                    ['name' => 'Qualitätskontrolle bestanden', 'order_index' => 3, 'is_required' => true],
                ],
            ],
            [
                'key' => 'management',
                'name' => 'Geschäftsleitung',
                'color' => '#198754',
                'icon' => 'bi-clipboard-check',
                'order_index' => 4,
                'required_role' => 'admin',
                'steps' => [
                    ['name' => 'Endabnahme durch Geschäftsleitung', 'order_index' => 1, 'is_required' => true],
                    ['name' => 'Rechnung erstellt', 'order_index' => 2, 'is_required' => true],
                    ['name' => 'Auslieferung dokumentiert', 'order_index' => 3, 'is_required' => true],
                ],
            ],
        ];

        foreach ($stages as $payload) {
            $steps = $payload['steps'] ?? [];
            unset($payload['steps']);

            $stage = Stage::updateOrCreate(
                ['key' => $payload['key']],
                $payload
            );

            foreach ($steps as $step) {
                Step::updateOrCreate(
                    [
                        'stage_id' => $stage->id,
                        'order_index' => $step['order_index'],
                    ],
                    [
                        'name' => $step['name'],
                        'is_required' => $step['is_required'] ?? true,
                    ]
                );
            }
        }
    }
}
