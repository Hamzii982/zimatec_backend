<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin Auftrag list links populated company order numbers to the owning detail page', function () {
    $project = Project::factory()->create([
        'project_name' => 'Werkzeug Auftrag',
        'auftragsnummer_zf' => 'ZF-1001',
        'auftragsnummer_zt' => 'ZT-2001',
    ]);
    $projectWithoutZtNumber = Project::factory()->create([
        'project_name' => 'Auftrag ohne ZT Nummer',
        'auftragsnummer_zf' => 'ZF-1002',
        'auftragsnummer_zt' => null,
    ]);

    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $response = $this->get(route('admin.projects'));

    $response->assertOk()
        ->assertSee(
            'href="'.route('admin.projects.show', $project).'" class="zt-tag" title="Auftrag anzeigen">ZT: ZT-2001',
            false,
        )
        ->assertSee(
            'href="'.route('admin.projects.show', $project).'" class="zt-tag" title="Auftrag anzeigen">ZF: ZF-1001',
            false,
        )
        ->assertSee(
            'href="'.route('admin.projects.show', $projectWithoutZtNumber).'" class="zt-tag" title="Auftrag anzeigen">ZF: ZF-1002',
            false,
        )
        ->assertDontSee(
            'href="'.route('admin.projects.show', $projectWithoutZtNumber).'" class="zt-tag" title="Auftrag anzeigen">ZT:',
            false,
        );
});

test('Auftragsnummer links do not expose the admin detail page to front users', function () {
    $project = Project::factory()->create([
        'project_name' => 'Front User Auftrag',
        'auftragsnummer_zf' => 'ZF-3001',
        'auftragsnummer_zt' => 'ZT-4001',
    ]);

    $response = $this->get(route('projects'));

    $response->assertOk()
        ->assertDontSee(route('admin.projects.show', $project), false)
        ->assertSee('ZF-3001')
        ->assertSee('ZT-4001');

    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('admin.projects.show', $project))
        ->assertForbidden();
});
