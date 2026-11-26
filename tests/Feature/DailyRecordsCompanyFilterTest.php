<?php

use App\Models\Machine;
use App\Models\MachineStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('daily records match either project order number while staying in the selected user company', function () {
    $project = Project::factory()->create([
        'auftragsnummer_zf' => 'ZF-100',
        'auftragsnummer_zt' => null,
    ]);
    $machine = Machine::factory()->create(['company' => 'ZT']);
    $positionId = DB::table('positions')->insertGetId([
        'project_id' => $project->id,
        'name' => 'Position 1',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $status = MachineStatus::firstOrCreate(['name' => 'Rustzeit']);
    $startTime = '2026-02-02 08:00:00';
    $endTime = '2026-02-02 08:30:00';
    $ztUserName = null;

    foreach (['ZF', 'ZT'] as $company) {
        $user = User::factory()->create(['company' => $company]);
        if ($company === 'ZT') {
            $ztUserName = $user->name;
        }

        $timeRecordId = DB::table('time_records')->insertGetId([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'position_id' => $positionId,
            'machine_id' => $machine->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('time_logs')->insert([
            'time_record_id' => $timeRecordId,
            'machine_status_id' => $status->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $dailyRecords = $this->getJson(route('admin.time.daily-records', [
        'calendar_week' => '202606',
        'auftragsnummer' => 'ZF-100',
        'position_id' => $positionId,
        'machine_id' => $machine->id,
        'user_company' => 'ZT',
    ]))->assertOk()->json('dailyRecords');

    expect($dailyRecords)->toHaveCount(1)
        ->and($dailyRecords[0]['company'])->toBe('ZT')
        ->and($dailyRecords[0]['auftragsnummer'])->toBe('ZF-100');

    $zfDailyRecords = $this->getJson(route('admin.time.daily-records', [
        'calendar_week' => '202606',
        'auftragsnummer' => 'ZF-100',
        'position_id' => $positionId,
        'machine_id' => $machine->id,
        'user_company' => 'ZF',
    ]))->assertOk()->json('dailyRecords');

    expect($zfDailyRecords)->toHaveCount(1)
        ->and($zfDailyRecords[0]['company'])->toBe('ZF')
        ->and($zfDailyRecords[0]['daily_key'])->not->toBe($dailyRecords[0]['daily_key']);

    $entries = $this->getJson('/admin/time/daily-records/details?'.http_build_query([
        'date' => '2026-02-02',
        'calendar_week' => '202606',
        'auftragsnummer' => 'ZF-100',
        'position_id' => $positionId,
        'machine_id' => $machine->id,
        'user_company' => 'ZT',
    ]))->assertOk()->json('entries');

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['user_name'])->toBe($ztUserName);

    $this->get(route('admin.time.records', ['week' => '202606']))
        ->assertOk()
        ->assertSee('getDailyRecords')
        ->assertSee('ZF-100');
});
