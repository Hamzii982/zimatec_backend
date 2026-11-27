<?php

use App\Models\Bauteil;
use App\Models\Position;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('project create and update redirect to the project detail page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $status = ProjectStatus::create(['name' => 'Neu']);

    $response = $this->actingAs($admin)->post(route('admin.projects.store'), [
        'project_name' => 'Redirect Test',
        'project_status_id' => $status->id,
        'auftragsnummer_zt' => 'ZT-REDIRECT-01',
        'save_to_db' => '1',
    ]);

    $project = Project::where('project_name', 'Redirect Test')->firstOrFail();
    $response->assertRedirect(route('admin.projects.show', $project));

    $this->put(route('admin.projects.update', $project), [
        'project_name' => 'Updated Redirect Test',
        'project_status_id' => $status->id,
        'auftragsnummer_zt' => 'ZT-REDIRECT-01',
    ])->assertRedirect(route('admin.projects.show', $project));
});

test('position create and update redirect to the owning project detail page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $project = Project::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.projects.positions.store', $project), [
            'name' => 'Neue Position',
        ]);
    $position = Position::where('project_id', $project->id)->firstOrFail();
    $response->assertRedirect(route('admin.projects.show', $project));

    $this->put(route('admin.projects.positions.update', [$project, $position]), [
        'name' => 'Aktualisierte Position',
    ])->assertRedirect(route('admin.projects.show', $project));
});

test('Bauteil create and update redirect to the assigned project detail page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $project = Project::factory()->create();
    $newProject = Project::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.bauteile.store'), [
        'name' => 'Neues Bauteil',
        'project_id' => $project->id,
    ]);
    $bauteil = Bauteil::where('name', 'Neues Bauteil')->firstOrFail();
    $response->assertRedirect(route('admin.projects.show', $project));

    $this->put(route('admin.bauteile.update', $bauteil), [
        'name' => 'Aktualisiertes Bauteil',
        'project_id' => $newProject->id,
    ])->assertRedirect(route('admin.projects.show', $newProject));
});
