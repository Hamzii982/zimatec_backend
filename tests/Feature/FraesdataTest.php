<?php

use App\Models\Bauteil;
use App\Models\Fraesdata;
use App\Models\Lager;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Position;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

test('fraesdata tables contain document targets and material tool details', function () {
    expect(Schema::hasColumns('fraesdata', [
        'id',
        'project_id',
        'position_id',
        'bauteil_id',
        'related_position_id',
        'name',
        'document_path',
        'uploaded_by',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('fraesdata_tools', [
            'id',
            'fraesdata_id',
            'material_id',
            'tool_number',
            'notes',
        ]))->toBeTrue();
});

test('fraesdata models connect projects targets and material tool rows', function () {
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);
    $bauteil = Bauteil::create([
        'project_id' => $project->id,
        'name' => 'Baugruppe',
    ]);
    $material = Material::create([
        'name' => 'Fräser',
        'description' => 'Hartmetall Fräser',
    ]);

    $positionDocument = Fraesdata::create([
        'project_id' => $project->id,
        'position_id' => $position->id,
        'name' => 'Position CNC',
        'document_path' => 'fraesdata/position.pdf',
    ]);
    $bauteilDocument = Fraesdata::create([
        'project_id' => $project->id,
        'bauteil_id' => $bauteil->id,
        'related_position_id' => $position->id,
        'name' => 'Bauteil CNC',
        'document_path' => 'fraesdata/bauteil.pdf',
    ]);
    $tool = $positionDocument->tools()->create([
        'material_id' => $material->id,
        'tool_number' => 'T08',
        'notes' => 'Für die Kontur',
    ]);

    expect($positionDocument->fresh()->project->is($project))->toBeTrue()
        ->and($positionDocument->fresh()->position->is($position))->toBeTrue()
        ->and($bauteilDocument->fresh()->bauteil->is($bauteil))->toBeTrue()
        ->and($bauteilDocument->fresh()->relatedPosition->is($position))->toBeTrue()
        ->and($tool->material->is($material))->toBeTrue()
        ->and($project->fresh()->fraesdata)->toHaveCount(2);
});

test('admin can upload a Position CNC PDF with multiple material rows', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);
    $firstMaterial = Material::create(['name' => 'Fräser 1', 'is_werkzeug' => true]);
    $secondMaterial = Material::create(['name' => 'Aluminium']);

    $this->actingAs($admin)
        ->post(route('admin.fraesdata.store', $project), [
            'name' => 'Drehteil NC',
            'position_id' => $position->id,
            'document' => UploadedFile::fake()->createWithContent('drehteil.pdf', "%PDF-1.4\nCNC test"),
            'materials' => [
                ['material_id' => $firstMaterial->id, 'tool_number' => 'T08', 'notes' => 'Kontur'],
                ['material_id' => $secondMaterial->id, 'tool_number' => '2', 'notes' => 'Rohmaterial'],
            ],
        ])
        ->assertRedirect(route('admin.projects.show', $project));

    $document = Fraesdata::with('tools')->firstOrFail();

    expect($document->name)->toBe('Drehteil NC')
        ->and($document->position_id)->toBe($position->id)
        ->and($document->tools)->toHaveCount(2)
        ->and($document->tools->pluck('tool_number')->all())->toBe(['T08', '2']);

    Storage::disk('local')->assertExists($document->document_path);
});

test('admin can upload a Bauteil PDF with an optional Position from the same project', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Gehäuseposition',
    ]);
    $bauteil = Bauteil::create([
        'project_id' => $project->id,
        'name' => 'Gehäuse',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.fraesdata.store', $project), [
            'name' => 'Gehäuse CNC',
            'bauteil_id' => $bauteil->id,
            'related_position_id' => $position->id,
            'document' => UploadedFile::fake()->createWithContent('gehaeuse.pdf', "%PDF-1.4\nCNC test"),
        ])
        ->assertRedirect(route('admin.projects.show', $project));

    $document = Fraesdata::firstOrFail();

    expect($document->bauteil_id)->toBe($bauteil->id)
        ->and($document->related_position_id)->toBe($position->id)
        ->and($document->position_id)->toBeNull();
});

test('admin can upload JPG and XML design files', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);

    foreach ([
        ['name' => 'Drehteil Bild', 'file' => UploadedFile::fake()->image('drehteil.jpg')],
        ['name' => 'Drehteil Daten', 'file' => UploadedFile::fake()->createWithContent('drehteil.xml', '<?xml version="1.0"?><design/>')],
    ] as $upload) {
        $this->actingAs($admin)
            ->post(route('admin.fraesdata.store', $project), [
                'name' => $upload['name'],
                'position_id' => $position->id,
                'document' => $upload['file'],
            ])
            ->assertRedirect(route('admin.projects.show', $project));
    }

    $documents = Fraesdata::all();
    expect($documents)->toHaveCount(2)
        ->and($documents->map(fn (Fraesdata $document) => pathinfo($document->document_path, PATHINFO_EXTENSION))->sort()->values()->all())
        ->toBe(['jpg', 'xml']);
    foreach ($documents as $document) {
        Storage::disk('local')->assertExists($document->document_path);
    }
});

test('admin cannot associate a Bauteil document with a Position from another project', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $bauteil = Bauteil::create([
        'project_id' => $project->id,
        'name' => 'Gehäuse',
    ]);
    $otherPosition = Position::create([
        'project_id' => $otherProject->id,
        'name' => 'Fremde Position',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.fraesdata.create', ['project' => $project, 'bauteil_id' => $bauteil->id]))
        ->post(route('admin.fraesdata.store', $project), [
            'name' => 'Gehäuse CNC',
            'bauteil_id' => $bauteil->id,
            'related_position_id' => $otherPosition->id,
            'document' => UploadedFile::fake()->createWithContent('gehaeuse.pdf', "%PDF-1.4\nCNC test"),
        ])
        ->assertSessionHasErrors('related_position_id');

    expect(Fraesdata::count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('fraesdata');
});

test('admin material search matches names descriptions and article numbers', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $nameMatch = Material::create(['name' => 'Titanfräser']);
    $descriptionMatch = Material::create([
        'name' => 'Rohmaterial',
        'description' => 'Werkzeug für Aluminium',
    ]);
    $codeMatch = Material::create([
        'name' => 'Stahlplatte',
        'code' => 'ART-9001',
    ]);
    Material::create(['name' => 'Schraube']);

    $this->actingAs($admin)
        ->getJson(route('admin.fraesdata.materials.search', ['q' => 'Titan']))
        ->assertOk()
        ->assertJsonFragment(['id' => $nameMatch->id, 'name' => 'Titanfräser']);

    $this->getJson(route('admin.fraesdata.materials.search', ['q' => 'Werkzeug']))
        ->assertOk()
        ->assertJsonFragment(['id' => $descriptionMatch->id, 'name' => 'Rohmaterial']);

    $this->getJson(route('admin.fraesdata.materials.search', ['q' => 'ART-9001']))
        ->assertOk()
        ->assertJsonFragment(['id' => $codeMatch->id, 'name' => 'Stahlplatte', 'code' => 'ART-9001']);
});

test('CNC upload and material search are limited to admins', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.fraesdata.create', ['project' => $project, 'position_id' => 1]))
        ->assertForbidden();

    $this->getJson(route('admin.fraesdata.materials.search', ['q' => 'steel']))
        ->assertForbidden();
});

test('admin upload form keeps the selected Position as the document target', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.fraesdata.create', [
            'project' => $project,
            'position_id' => $position->id,
        ]))
        ->assertOk()
        ->assertSee(__('fraesdata.upload.title'))
        ->assertSee(__('fraesdata.position'))
        ->assertSee('Drehteil')
        ->assertSee('name="position_id" value="'.$position->id.'"', false)
        ->assertSee($project->project_name)
        ->assertSee('accept=".pdf,.jpg,.jpeg,.xml', false)
        ->assertSee('id="material-search"', false);
});

test('CNC upload rejects unsupported and oversized files', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);
    $route = route('admin.fraesdata.store', $project);

    $this->actingAs($admin)
        ->from(route('admin.fraesdata.create', ['project' => $project, 'position_id' => $position->id]))
        ->post($route, [
            'name' => 'Ungültige Datei',
            'position_id' => $position->id,
            'document' => UploadedFile::fake()->createWithContent('not-a-pdf.txt', 'plain text'),
        ])
        ->assertSessionHasErrors('document');

    $this->from(route('admin.fraesdata.create', ['project' => $project, 'position_id' => $position->id]))
        ->post($route, [
            'name' => 'Zu große Datei',
            'position_id' => $position->id,
            'document' => UploadedFile::fake()->create('too-large.pdf', 20481, 'application/pdf'),
        ])
        ->assertSessionHasErrors('document');

    expect(Fraesdata::count())->toBe(0);
});

test('public project list links projects and displays their latest CNC upload', function () {
    $status = ProjectStatus::create([
        'name' => 'In Bearbeitung',
        'color' => '#0d5ca8',
    ]);
    $project = Project::factory()->create([
        'project_name' => 'Projekt mit Fräsdaten',
        'auftragsnummer_zt' => 'ZT-3001',
        'auftragsnummer_zf' => 'ZF-4001',
        'project_status_id' => $status->id,
    ]);
    Project::factory()->create([
        'project_name' => 'Projekt ohne CNC-Daten',
        'auftragsnummer_zt' => 'ZT-3002',
    ]);
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);
    Bauteil::create([
        'project_id' => $project->id,
        'name' => 'Gehäuse',
    ]);
    $document = Fraesdata::create([
        'project_id' => $project->id,
        'position_id' => $position->id,
        'name' => 'Aktueller Drehplan',
        'document_path' => 'fraesdata/aktueller-drehplan.pdf',
    ]);

    $this->get(route('projects'))
        ->assertOk()
        ->assertSee(route('projects.cnc-data', $project), false)
        ->assertSee('Projekt mit Fräsdaten')
        ->assertSee(__('fraesdata.cnc_data_recent', ['date' => $document->created_at->format('d.m.Y')]))
        ->assertSee($document->created_at->format('d.m.Y H:i'));
    $this->get(route('projects'))
        ->assertSee('data-project-search="Projekt mit Fräsdaten ZT-3001 ZF-4001"', false)
        ->assertSee('data-status-filter="'.$status->id.'"', false)
        ->assertSee('data-project-status="'.$status->id.'"', false)
        ->assertSee('1 '.trans_choice('fraesdata.positions_count', 1), false)
        ->assertSee('1 '.trans_choice('fraesdata.bauteile_count', 1), false)
        ->assertSee(__('fraesdata.project_search_hint'))
        ->assertSee(__('fraesdata.no_cnc_data'));
});

test('public project list hides CNC document button when no upload exists', function () {
    Project::factory()->create(['project_name' => 'Projekt ohne CNC']);

    $this->get(route('projects'))
        ->assertOk()
        ->assertSee(__('fraesdata.no_cnc_data'))
        ->assertDontSee(__('fraesdata.view_documents'));
});

test('public project CNC page lists selectable Positions and Bauteile', function () {
    $project = Project::factory()->create([
        'project_name' => 'CNC Auftrag',
        'auftragsnummer_zf' => 'ZF-9012',
        'auftragsnummer_zt' => 'ZT-3456',
    ]);
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);
    $bauteil = Bauteil::create([
        'project_id' => $project->id,
        'name' => 'Gehäuse',
    ]);

    $this->get(route('projects.cnc-data', $project))
        ->assertOk()
        ->assertSee('CNC Auftrag')
        ->assertSee('ZF-9012')
        ->assertSee('ZT-3456')
        ->assertDontSee(__('fraesdata.title').' — CNC Auftrag')
        ->assertSee('Drehteil')
        ->assertSee('Gehäuse')
        ->assertSee(e(route('projects.cnc-data', [
            'project' => $project,
            'target_type' => 'position',
            'target_id' => $position->id,
        ])), false)
        ->assertSee(e(route('projects.cnc-data', [
            'project' => $project,
            'target_type' => 'bauteil',
            'target_id' => $bauteil->id,
        ])), false);
});

test('public project CNC page shows selected target documents and material details', function () {
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);
    $material = Material::create([
        'name' => 'Schaftfräser',
        'code' => 'ART-1004',
        'description' => 'Für Aluminium',
    ]);
    $document = Fraesdata::create([
        'project_id' => $project->id,
        'position_id' => $position->id,
        'name' => 'Drehteil Bearbeitungsplan',
        'document_path' => 'fraesdata/drehteil.pdf',
    ]);
    $document->tools()->create([
        'material_id' => $material->id,
        'tool_number' => 'T04',
        'notes' => 'Schruppen',
    ]);

    $this->get(route('projects.cnc-data', [
        'project' => $project,
        'target_type' => 'position',
        'target_id' => $position->id,
    ]))
        ->assertOk()
        ->assertSee('Drehteil Bearbeitungsplan')
        ->assertSee('Schaftfräser')
        ->assertSee(__('fraesdata.upload.article_number').' ART-1004')
        ->assertSee('T04')
        ->assertSee('Schruppen')
        ->assertSee(trans_choice('fraesdata.document_count', 1, ['count' => 1]))
        ->assertSee(__('fraesdata.latest_upload'))
        ->assertSee(__('fraesdata.recent_upload').':')
        ->assertSee($document->created_at->format('d.m.Y H:i'))
        ->assertSee(route('projects.cnc-data.download', [$project, $document]), false)
        ->assertSee(route('projects.cnc-data.print', [$project, $document]), false);
});

test('public CNC material details show Lager location and aggregated reservation actions', function () {
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);
    $lager = Lager::create(['name' => 'Werkzeuglager']);
    $shelf = Shelf::create([
        'lager_id' => $lager->id,
        'name' => 'Regal A',
    ]);
    $material = Material::create([
        'name' => 'Schaftfräser',
        'lager_id' => $lager->id,
        'shelf_id' => $shelf->id,
        'quantity' => 3,
        'on_hold_quantity' => 2,
    ]);
    $document = Fraesdata::create([
        'project_id' => $project->id,
        'position_id' => $position->id,
        'name' => 'Drehteil Plan',
        'document_path' => 'fraesdata/drehteil.pdf',
    ]);
    $document->tools()->create(['material_id' => $material->id]);

    $this->get(route('projects.cnc-data', [
        'project' => $project,
        'target_type' => 'position',
        'target_id' => $position->id,
    ]))
        ->assertOk()
        ->assertSee('Werkzeuglager')
        ->assertSee('Regal A')
        ->assertSee('data-available="3"', false)
        ->assertSee('data-on-hold="2"', false)
        ->assertSee(route('tablar.reserve', $lager->id), false)
        ->assertSee(route('tablar.reserve.release', $lager->id), false)
        ->assertSee(route('tablar.reserve.damage', $lager->id), false)
        ->assertSee(__('fraesdata.stock.aggregate_note'));
});

test('public users can reserve and return one unit through the aggregate CNC stock actions', function () {
    $lager = Lager::create(['name' => 'Werkzeuglager']);
    $material = Material::create([
        'name' => 'Schaftfräser',
        'lager_id' => $lager->id,
        'quantity' => 3,
        'on_hold_quantity' => 2,
    ]);

    $this->postJson(route('tablar.reserve', $lager->id), [
        'material_id' => $material->id,
        'quantity' => 1,
    ])
        ->assertOk()
        ->assertJson(['new_quantity' => 2, 'on_hold_quantity' => 3]);

    $this->postJson(route('tablar.reserve.release', $lager->id), [
        'material_id' => $material->id,
        'quantity' => 1,
    ])
        ->assertOk()
        ->assertJson(['new_quantity' => 3, 'on_hold_quantity' => 2]);

    expect($material->fresh()->quantity)->toBe(3)
        ->and($material->fresh()->on_hold_quantity)->toBe(2)
        ->and(MaterialConsumption::where('material_id', $material->id)
            ->whereIn('consumption_type', ['reserve', 'return'])
            ->count())->toBe(2);
});

test('damaged reserved stock is written off without deleting the material record', function () {
    $lager = Lager::create(['name' => 'Werkzeuglager']);
    $material = Material::create([
        'name' => 'Schaftfräser',
        'lager_id' => $lager->id,
        'quantity' => 3,
        'on_hold_quantity' => 2,
    ]);

    $this->postJson(route('tablar.reserve.damage', $lager->id), [
        'material_id' => $material->id,
        'quantity' => 1,
    ])
        ->assertOk()
        ->assertJson(['new_quantity' => 3, 'on_hold_quantity' => 1]);

    expect($material->fresh()->exists)->toBeTrue()
        ->and($material->fresh()->quantity)->toBe(3)
        ->and($material->fresh()->on_hold_quantity)->toBe(1)
        ->and(MaterialConsumption::where('material_id', $material->id)
            ->where('consumption_type', 'damaged')
            ->value('quantity'))->toBe(1);

    $this->postJson(route('tablar.reserve.release', $lager->id), [
        'material_id' => $material->id,
        'quantity' => 2,
    ])->assertStatus(400);
});

test('admin project page displays the latest CNC document as a badge', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);
    $document = Fraesdata::create([
        'project_id' => $project->id,
        'position_id' => $position->id,
        'name' => 'Drehteil Plan',
        'document_path' => 'fraesdata/drehteil.pdf',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.projects.show', $project))
        ->assertOk()
        ->assertSee('rounded-pill d-inline-flex align-items-center gap-1 px-2 py-2', false)
        ->assertSee(__('fraesdata.recent_upload').':')
        ->assertSee($document->created_at->format('d.m.Y H:i'));
});

test('public CNC page rejects a target belonging to another project', function () {
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $otherPosition = Position::create([
        'project_id' => $otherProject->id,
        'name' => 'Fremde Position',
    ]);

    $this->get(route('projects.cnc-data', [
        'project' => $project,
        'target_type' => 'position',
        'target_id' => $otherPosition->id,
    ]))->assertNotFound();
});

test('public PDF download is scoped to the requested project', function () {
    Storage::fake('local');
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);
    $documentPath = 'fraesdata/drehteil.pdf';
    Storage::disk('local')->put($documentPath, "%PDF-1.4\nCNC test");
    $document = Fraesdata::create([
        'project_id' => $project->id,
        'position_id' => $position->id,
        'name' => 'Drehteil Bearbeitungsplan',
        'document_path' => $documentPath,
    ]);

    $this->get(route('projects.cnc-data.download', [$project, $document]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('projects.cnc-data.download', [$otherProject, $document]))
        ->assertNotFound();
});

test('public design file downloads retain their supported extension and media type', function () {
    Storage::fake('local');
    $project = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);

    foreach ([
        ['extension' => 'jpg', 'mime' => 'image/jpeg'],
        ['extension' => 'xml', 'mime' => 'application/xml'],
    ] as $index => $file) {
        $path = "fraesdata/design-{$index}.{$file['extension']}";
        Storage::disk('local')->put($path, '<design/>');
        $document = Fraesdata::create([
            'project_id' => $project->id,
            'position_id' => $position->id,
            'name' => 'Design Datei '.$index,
            'document_path' => $path,
        ]);

        $this->get(route('projects.cnc-data.download', [$project, $document]))
            ->assertOk()
            ->assertHeader('content-type', $file['mime'])
            ->assertHeader('content-disposition', 'inline; filename=design-datei-'.$index.'.'.$file['extension']);
    }
});

test('public print page previews each supported design-file type and remains project-scoped', function () {
    Storage::fake('local');
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $position = Position::create([
        'project_id' => $project->id,
        'name' => 'Drehteil',
    ]);

    foreach (['pdf', 'jpg', 'xml'] as $extension) {
        $path = "fraesdata/print-design.{$extension}";
        Storage::disk('local')->put($path, '<design/>');
        $document = Fraesdata::create([
            'project_id' => $project->id,
            'position_id' => $position->id,
            'name' => 'Druck Test '.$extension,
            'document_path' => $path,
        ]);

        $response = $this->get(route('projects.cnc-data.print', [$project, $document]))
            ->assertOk()
            ->assertViewHas('fileUrl', route('projects.cnc-data.download', [$project, $document]));

        $previewElement = match ($extension) {
            'pdf' => 'id="print-frame"',
            'jpg' => 'id="print-image"',
            'xml' => 'id="print-xml"',
        };
        $response->assertSee($previewElement, false);

        $this->get(route('projects.cnc-data.print', [$otherProject, $document]))
            ->assertNotFound();
    }
});
