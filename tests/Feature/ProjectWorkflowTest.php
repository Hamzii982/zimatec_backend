<?php

use App\Models\Project as BaseProject;
use App\Models\User;
use App\Models\Workflow\Activity;
use App\Models\Workflow\Project as WorkflowProject;
use App\Models\Workflow\ProjectStep;
use App\Models\Workflow\Stage;
use Database\Seeders\WorkflowStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed the four canonical workflow stages + step templates.
    $this->seed(WorkflowStageSeeder::class);
});

test('board lists projects grouped by current stage', function () {
    $user = User::factory()->create(['role' => 'user']);
    $this->actingAs($user);

    $office = Stage::where('key', 'office')->firstOrFail();
    $design = Stage::where('key', 'design')->firstOrFail();

    $p1 = BaseProject::factory()->create(['project_name' => 'Projekt A']);
    $p2 = BaseProject::factory()->create(['project_name' => 'Projekt B']);

    $service = app(\App\Services\Workflow\WorkflowService::class);

    $wp1 = $service->attachProject($p1, $office, $user);
    $wp2 = $service->attachProject($p2, $design, $user);

    $response = $this->get(route('workflow.index'));

    $response->assertOk();

    $grouped = $response->viewData('grouped');
    expect($grouped->keys()->all())
        ->toEqual([$office->id, $design->id]);
    expect($grouped[$office->id]->pluck('id')->all())->toContain($wp1->id);
    expect($grouped[$design->id]->pluck('id')->all())->toContain($wp2->id);
});

test('completing a step moves it to completed and writes a step_completed activity', function () {
    $user = User::factory()->create(['role' => 'user']);
    $this->actingAs($user);

    $office = Stage::where('key', 'office')->firstOrFail();

    $project = BaseProject::factory()->create();
    $service = app(\App\Services\Workflow\WorkflowService::class);
    $workflowProject = $service->attachProject($project, $office, $user);

    $step = $workflowProject->projectSteps()->first();

    $response = $this->postJson(route('workflow.steps.complete', [$project->id, $step->id]), [
        'note' => 'Alles erledigt',
    ]);

    $response->assertOk();

    $step->refresh();
    expect($step->status)->toBe('completed');
    expect($step->completed_by)->toBe($user->id);
    expect($step->note)->toBe('Alles erledigt');

    expect(Activity::where('type', 'step_completed')->count())->toBe(1);
});

test('advancing a stage dispatches a notification to the new assignee', function () {
    // Make admin the actor so the policy allows the action.
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $office = Stage::where('key', 'office')->firstOrFail();
    $design = Stage::where('key', 'design')->firstOrFail();

    $project = BaseProject::factory()->create();

    $service = app(\App\Services\Workflow\WorkflowService::class);
    $advancer = app(\App\Services\Workflow\StageAdvancer::class);

    $wp = $service->attachProject($project, $office, $admin);

    // Complete every required step in the office stage
    foreach ($wp->projectSteps as $step) {
        $service->completeStep($wp->fresh('projectSteps'), $step, $admin, null);
    }

    expect($advancer->canAdvance($wp->fresh('projectSteps')))->toBeTrue();

    $response = $this->postJson(route('workflow.advance', $project->id));

    $response->assertOk();

    $wp->refresh();
    expect($wp->current_stage_id)->toBe($design->id);

    expect(Activity::where('type', 'stage_advanced')->count())->toBe(1);

    expect(\App\Models\Notification::where('type', 'workflow_stage')->count())->toBe(1);
});

test('non-assignee non-admin user cannot advance a project', function () {
    $assignee = User::factory()->create(['role' => 'user']);
    $intruder = User::factory()->create(['role' => 'user']);

    $office = Stage::where('key', 'office')->firstOrFail();
    $project = BaseProject::factory()->create();

    $service = app(\App\Services\Workflow\WorkflowService::class);
    $wp = $service->attachProject($project, $office, $assignee);

    // Complete every required step so canAdvance() is true (so we exercise
    // the policy, not the precondition).
    foreach ($wp->projectSteps as $step) {
        $service->completeStep($wp, $step, $assignee, null);
    }

    $this->actingAs($intruder);

    $response = $this->postJson(route('workflow.advance', $project->id));

    $response->assertForbidden();
});
