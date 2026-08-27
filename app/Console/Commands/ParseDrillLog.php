<?php

namespace App\Console\Commands;

use App\Models\Bauteil;
use App\Models\Position;
use App\Models\Procedure;
use App\Models\Process;
use App\Models\ProcessPause;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

class ParseDrillLog extends Command
{
    protected $signature = 'parse:drilllog {path} {--save=true : Persist parsed data to the database}';

    protected $description = 'Parse drilling machine log and store data in database';

    private function init($state)
    {
        if (! file_exists($state->path)) {

            $this->error("File not found: $state->path");

            return 1;
        }

        $this->info('Save to DB: '.($state->saveToDb ? 'YES' : 'NO (dry-run)'));

        $lines = file($state->path, FILE_IGNORE_NEW_LINES);

        $this->info('Loaded '.count($lines).' lines.');

        return $lines;
    }

    public function handle()
    {
        $state = new ParserState;
        $state->path = $this->argument('path');
        $state->saveToDb = filter_var($this->option('save'), FILTER_VALIDATE_BOOLEAN);

        $lines = $this->init($state);

        $state->processedDir = storage_path('app/public').'/processed/';
        $file_name = basename($state->path);

        foreach ($lines as $i => $line) {
            $ln = trim($line);

            // --- STEP 1: Detect log date ---
            if ($this->detectLogDate($ln, $state)) {
                continue;
            }
            // Skip any lines before log date
            if (! $state->logDate) {
                continue;
            }

            // --- STEP 2: START DER PROZEDUR ---
            // if($this->detectProcedureStart($ln, $state)) continue;

            // --- STEP 3: Process start (TO CNC...) ---
            if ($this->detectProcessStart($ln, $state)) {
                continue;
            }

            // --- STEP 3.1: PAUSE START ---
            if ($state->currentProcName && ! $state->currentPause) {

                // M00 Program Stop
                if ($this->detectNoPauseM00($ln, $state)) {
                    continue;
                }

                // HOLD key
                if ($this->detectNoPauseHold($ln, $state)) {
                    continue;
                }
            }

            // --- STEP 3.2: PAUSE END ---
            if ($state->currentPause) {

                // Resume from M00
                if ($state->currentPause['type'] === 'M00_PROGRAM_STOP') {
                    if ($this->detectPauseM00($ln, $state)) {
                        continue;
                    }
                }

                // Resume from HOLD
                if ($state->currentPause['type'] === 'HOLD_KEY') {
                    if ($this->detectPauseHold($ln, $state)) {
                        continue;
                    }
                }
            }

            // --- STEP 4: Execution time ---
            if ($this->detectProcessEnd($ln, $state)) {
                continue;
            }

            // --- STEP 5: END OF PROCEDURE ---
            // if($this->detectProcedureEnd($ln, $state)) continue;
        }

        $this->shouldStoreProcedure($state);

        $this->finalize($state, $file_name);

        return 0;
    }

    private function finalize($state, $file_name)
    {
        if ($state->saveToDb) {
            copy($state->path, $state->processedDir.$file_name);
        }

        $this->info('✅ Parsing complete.');
    }

    private function detectLogDate($line, $state)
    {
        if (! preg_match('/<<\s*(\d{1,2}-[A-Za-z]{3}-\d{4})\s*>>/', $line, $m)) {
            return false;
        }
        if (! $state->firstDate) {
            $state->firstDate = Carbon::createFromFormat('d-M-Y', $m[1]);

            $year = $state->firstDate->format('Y');
            $week = $state->firstDate->format('W');

            $state->processedDir = storage_path('app/public')."/processed/{$year}/{$week}/";
            if (! is_dir($state->processedDir)) {
                mkdir($state->processedDir, 0755, true);
            }
        }

        $state->logDate = $m[1];
        $this->info("📅 Detected log date: $state->logDate");

        return true;
    }

    private function detectProcedureStart($line, $state)
    {
        if (! (preg_match('/START DER PROZEDUR.*\((.*?)\)/i', $line, $m))) {
            return false;
        }
        $pathInside = $m[1];
        $parts = preg_split('/[\\\\\/]+/', $pathInside);
        if (count($parts) >= 2) {
            $projectName = $parts[count($parts) - 2];
            $auftragsnummer = explode('_', $projectName)[0];
            $projectName = Str::after($projectName, '_');
        } else {
            $projectName = null;
            $auftragsnummer = null;
        }

        if (preg_match('/\b(\d{2}:\d{2}:\d{2})\b/', $line, $t)) {
            // Combine log date with time
            $startTime = Carbon::createFromFormat('d-M-Y H:i:s', "$state->logDate {$t[1]}")->toDateTimeString();
        } else {
            $startTime = null;
        }

        // find or create project
        if ($state->saveToDb) {
            $company = $this->auftragsnummerCompany($auftragsnummer);
            $state->currentProject = Project::firstOrCreate(
                ['auftragsnummer_'.$company => $auftragsnummer, 'project_name' => $projectName, 'from_machine_logs' => 1]
            );
        } else {
            $state->currentProject = new Project([
                'id' => null,
                'project_name' => $projectName,
                'auftragsnummer' => $auftragsnummer,
            ]);
        }
        // $state->currentProject = Project::firstOrCreate(
        //     ['auftragsnummer_zt' => $auftragsnummer, 'project_name' => $projectName, 'from_machine_logs' => 1]
        // );

        // store previous procedure if any
        if ($state->currentProcedure) {
            $this->storeProcedure($state->currentProject, $state->currentProcedure, $state->processMap);
        }

        // start new procedure
        $state->currentProcedure = [
            'start_time' => $startTime,
            'end_time' => null,
            'source_file' => $pathInside,
        ];
        $state->processMap = [];
        $state->currentProcName = null;

        $this->info("## STARTED Procedure for: {$projectName} ({$auftragsnummer})");

        return true;
    }

    private function detectProcessStart($line, $state)
    {
        if (! (preg_match('/TO CNC:([^\\)]+?)(?:\.fid|\s|\))/i', $line, $m))) {
            return false;
        }
        $procName = trim($m[1]);
        $procName = str_replace(['\\', '/'], '_', $procName);
        $state->currentProcName = $procName;
        $state->currentPause = null;
        $state->pauseBuffer = [];

        // Extract full path after "FROM IPC:"
        if (preg_match('/FROM IPC:([^\s]+)/i', $line, $matches)) {
            $fullPath = $matches[1];
            $state->sourceFile = mb_convert_encoding($fullPath, 'UTF-8', 'auto');

            $normalizedPath = str_replace('\\', '/', $fullPath);
            $parts = explode('/', $normalizedPath);
            $filename = basename($normalizedPath);

            $projectName = null;
            $auftragsnummer = null;
            $bauteil = null;
            $position = null;
            if (strtoupper($filename) === 'NULLEN.FID' || substr(strtolower($filename), -4) !== '.fid') {
                return true;
            }

            // --- basic validation ---
            if (count($parts) < 5) {
                throw new RuntimeException('Path too short to be a valid CNC file '.$filename.' in path '.$fullPath);
            }

            $machine = $parts[1];
            $this->machineSelection(strtolower($machine), $state);
            $projectPart = $parts[2];
            $position = $parts[3];
            $bauteil = $parts[4] ?? null;
            // $file         = $parts[count($parts) - 1];

            $auftragsnummer = $projectPart;
            $positionNumber = explode('_', $position, 2)[0];

            if ($state->saveToDb) {
                $state->currentProject = Project::where('auftragsnummer_zt', $auftragsnummer)
                    ->orWhere('auftragsnummer_zf', $auftragsnummer)
                    ->first();

                if (! $state->currentProject) {
                    $this->info("Project not found for auftragsnummer {$auftragsnummer} — skipping process");

                    return true;
                }

                $state->currentPosition = Position::where('project_id', $state->currentProject->id)
                    ->where('name', 'like', $positionNumber.'%')
                    ->first();

                if (! $state->currentPosition) {
                    $this->info("Position {$positionNumber} not found in project {$auftragsnummer} — skipping process");

                    return true;
                }

                if ($bauteil !== null) {
                    $state->currentBauteil = Bauteil::firstOrCreate(
                        [
                            'name' => mb_convert_encoding($bauteil, 'UTF-8', 'auto'),
                            'project_id' => $state->currentProject->id,
                            'parent_id' => null,
                        ]
                    );
                }

            } else {
                $state->currentProject = new Project([
                    'id' => null,
                    'project_name' => $projectName,
                    'auftragsnummer' => $auftragsnummer,
                ]);

                $state->currentPosition = new Position(
                    [
                        'id' => null,
                        'name' => $position,
                        'project_id' => $state->currentProject->id,
                    ]
                );

                if ($bauteil !== null) {
                    $state->currentBauteil = new Bauteil([
                        'id' => null,
                        'name' => mb_convert_encoding($bauteil, 'UTF-8', 'auto'),
                        'project_id' => $state->currentProject->id,
                        'parent_id' => null,
                    ]);
                }

            }

            $this->info("Neue START fur projekt: {$projectName} ({$auftragsnummer}) auf Machine {$state->machineId}, {$machine}");
            $this->info("   └─ Postion: {$position}");
            if ($bauteil !== null) {
                $this->info("       └─ Bauteil: {$bauteil}");
            }
        }

        // combine time from log line
        $timeMatch = preg_match('/\b(\d{2}:\d{2}:\d{2})\b/', $line, $t);
        $state->procStartTime = $timeMatch
            ? Carbon::createFromFormat('d-M-Y H:i:s', "$state->logDate {$t[1]}")
            : null;

        if ($state->currentProcedure && $state->currentProject) {
            $state->processMap[$state->counter] = [
                'proc_name' => $procName,
                'total_seconds' => 0,
                'project_id' => $state->currentProject->id ?? 0,
                'position_id' => $state->currentPosition->id ?? null,
                'bauteil_id' => $state->currentBauteil->id ?? null,
                'machine_id' => $state->machineId,
                'start_time' => $state->procStartTime,
                'source_file' => $state->sourceFile ?? null,
                'pauses' => [],
            ];
        }
        // standalone (outside procedure)
        else {
            $state->standaloneName = $procName;
            $state->standaloneStart = $state->procStartTime;
        }

        return true;
    }

    private function detectNoPauseM00($line, ParserState $state)
    {
        if (! (preg_match('/\b(\d{2}:\d{2}:\d{2})\s+IEX_\d+\s+M00:\s+PROGRAM STOP\b/i', $line, $m))) {
            return false;
        }
        $state->currentPause = [
            'start' => Carbon::createFromFormat('d-M-Y H:i:s', "$state->logDate {$m[1]}"),
            'type' => 'M00_PROGRAM_STOP',
            'reason' => 'M00 Program Stop',
        ];

        return true;
    }

    private function detectNoPauseHold($line, ParserState $state)
    {
        if (! (preg_match('/\b(\d{2}:\d{2}:\d{2})\s+DPS_\d+\s+PRESSED THE HOLD KEY\b/i', $line, $m))) {
            return false;
        }
        $state->currentPause = [
            'start' => Carbon::createFromFormat('d-M-Y H:i:s', "$state->logDate {$m[1]}"),
            'type' => 'HOLD_KEY',
            'reason' => 'Pressed HOLD key',
        ];

        return true;
    }

    private function detectPauseM00($line, ParserState $state)
    {
        // Resume from M00
        if (! (preg_match('/\b(\d{2}:\d{2}:\d{2})\s+DPS_\d+\s+PRESSED THE START KEY\b/i', $line, $m))) {
            return false;
        }
        $state->currentPause['end'] = Carbon::createFromFormat('d-M-Y H:i:s', "$state->logDate {$m[1]}");
        $state->processMap[$state->counter]['pauses'][] = $state->currentPause;
        $state->pauseBuffer[] = $state->currentPause;
        $state->currentPause = null;

        return true;
    }

    private function detectPauseHold($line, ParserState $state)
    {
        if (! (preg_match('/\b(\d{2}:\d{2}:\d{2})\s+DPS_\d+\s+RELEASED THE RELEASE KEY\b/i', $line, $m))) {
            return false;
        }
        $state->currentPause['end'] = Carbon::createFromFormat('d-M-Y H:i:s', "$state->logDate {$m[1]}");
        $state->processMap[$state->counter]['pauses'][] = $state->currentPause;
        $state->pauseBuffer[] = $state->currentPause;
        $state->currentPause = null;

        return true;
    }

    private function detectProcessEnd($line, ParserState $state)
    {
        if (
            ! (preg_match('/\s(\d{2}:\d{2}:\d{2})\s+IEX_\d+\s+FILE EXECUTION TIME/', $line, $m)
            ||
            // CNC turned OFF because of an error
            preg_match('/\b(\d{1,2}:\d{2}:\d{2})\s+ICN_\d+\s+CNC OFF\b/i', $line, $m))
        ) {
            return false;
        }

        $endTime = Carbon::createFromFormat('d-M-Y H:i:s', "$state->logDate {$m[1]}");

        if ($state->currentPause) {
            $state->currentPause['end'] = $endTime;
            $state->pauseBuffer[] = $state->currentPause;
            $state->currentPause = null;
        }

        if ($state->currentProcName && $state->currentProcedure) {
            $seconds = $state->procStartTime->diffInSeconds($endTime);
            $state->processMap[$state->counter]['total_seconds'] = $seconds;
            $state->processMap[$state->counter]['end_time'] = $endTime;
            $state->counter++;
        } elseif ($state->standaloneName && $state->standaloneStart) {

            // Try to detect project from path name if possible (optional future logic)
            $seconds = $state->standaloneStart->diffInSeconds($endTime);
            $project = $state->currentProject;
            $state->standaloneName = preg_replace('/[^\x20-\x7E_\-\.]/', '', $state->standaloneName);

            if ($state->saveToDb) {
                $process = Process::firstOrCreate([
                    'project_id' => $project?->id,
                    'position_id' => $state->currentPosition->id ? $state->currentPosition->id : null,
                    'procedure_id' => null,
                    'bauteil_id' => (($state->currentBauteil->id) ? $state->currentBauteil->id : null),
                    'machine_id' => $state->machineId,
                    'name' => $state->standaloneName,
                    'start_time' => $state->standaloneStart,
                    'end_time' => $endTime,
                    'total_seconds' => $seconds,
                    'source_file' => $state->sourceFile,
                ]);

                // store pauses
                foreach ($state->pauseBuffer as $pause) {
                    ProcessPause::firstOrCreate([
                        'process_id' => $process->id,
                        'pause_start' => $pause['start'],
                        'pause_end' => $pause['end'] ?? null,
                        'pause_type' => $pause['type'],
                        'reason' => $pause['reason'],
                    ]);
                }

                // reset
                $state->pauseBuffer = [];
                $state->currentPause = null;
            } else {
                $this->line('🧪 Prozess Beschreibung:');
                $this->line(json_encode([
                    'name' => $state->standaloneName,
                    'start' => $state->standaloneStart,
                    'end' => $endTime,
                    'sekunden' => $seconds,
                    'Pfad' => $state->sourceFile,
                ], JSON_PRETTY_PRINT));
            }

            $this->info("💾 Neue Prozess Gespeichert: {$state->standaloneName} ({$seconds}s)");
            $state->standaloneName = null;
            $state->standaloneStart = null;
        }

        return true;
    }

    private function detectProcedureEnd($line, ParserState $state)
    {
        if (! preg_match('/ENDE DER PROZEDUR/i', $line)) {
            return false;
        }

        if ($state->currentProcedure && $state->currentProject) {
            if (preg_match('/\b(\d{2}:\d{2}:\d{2})\b/', $line, $t)) {
                $endTime = Carbon::createFromFormat('d-M-Y H:i:s', "$state->logDate {$t[1]}")->toDateTimeString();
                $state->currentProcedure['end_time'] = $endTime;
            }
            $this->storeProcedure($state->currentProject, $state->currentProcedure, $state->processMap, $state->saveToDb);
            $state->currentProcedure = null;
            $state->processMap = [];
        }

        return true;
    }

    private function shouldStoreProcedure(ParserState $state)
    {
        if ($state->currentProcedure && $state->currentProject) {
            $this->storeProcedure($state->currentProject, $state->currentProcedure, $state->processMap, $state->saveToDb);
        }
    }

    protected function storeProcedure(Project $project, array $procData, array $processMap, bool $saveToDb = true)
    {
        if (! $saveToDb) {
            $this->line('🧪 Procedure (dry-run):');
            $this->line(json_encode([
                'project' => $project->project_name ?? null,
                'start' => $procData['start_time'],
                'end' => $procData['end_time'],
                'processes' => $processMap,
            ], JSON_PRETTY_PRINT));

            return;
        }
        $exists = Procedure::where('project_id', $project->id)
            ->where('start_time', $procData['start_time'])
            ->first();

        if ($exists) {
            $this->info("⚠️ Skipping duplicate procedure for project {$project->project_name}");

            return;
        }

        $proc = $project->procedures()->firstOrCreate([
            'start_time' => $procData['start_time'],
            'end_time' => $procData['end_time'] ?? null,
            'process_count' => count($processMap),
            'source_file' => $procData['source_file'] ?? null,
        ]);

        foreach ($processMap as $name => $data) {
            $process = $proc->processes()->firstOrCreate([
                'name' => $data['proc_name'],
                'project_id' => $data['project_id'],
                'bauteil_id' => $data['bauteil_id'] ?? null,
                'machine_id' => $data['machine_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'source_file' => $data['source_file'],
                'total_seconds' => $data['total_seconds'] ?? 0,
            ]);

            foreach ($data['pauses'] ?? [] as $pause) {
                ProcessPause::firstOrcreate([
                    'process_id' => $process->id,
                    'pause_start' => $pause['start'],
                    'pause_end' => $pause['end'] ?? null,
                    'pause_type' => $pause['type'],
                    'reason' => $pause['reason'],
                ]);
            }
        }

        $this->info("💾 Stored procedure for project {$project->project_name} with ".count($processMap).' processes.');
    }

    protected function auftragsnummerCompany(int $auftrag)
    {
        $company = 'zt';
        if (substr($auftrag, 0, 1) == 3) {
            $company = 'zf';
        }

        return $company;
    }

    private function machineSelection(string $machine, ParserState $state)
    {
        if ($machine == 'auftraege_zt' || $machine == 'Auftraege_D-FZ37_ZiMaTec') {
            $state->machineId = 2;
        } elseif ($machine == 'auftraege_zf') {
            $state->machineId = 3;
        } else {
            $state->machineId = 1;
        }
    }
}

final class ParserState
{
    public ?string $path = null;

    public bool $saveToDb = false;

    public ?Carbon $firstDate = null;

    public ?string $processedDir = null;

    public ?string $logDate = null;

    public ?Project $currentProject = null;

    public ?array $currentProcedure = null;

    public ?string $currentProcName = null;

    public ?Carbon $procStartTime = null;

    public array $processMap = [];

    public int $counter = 0;

    public ?Carbon $standaloneStart = null;

    public ?string $standaloneName = null;

    public ?string $sourceFile = null;

    public ?Bauteil $currentBauteil = null;

    public ?Position $currentPosition = null;

    public ?array $currentPause = null;

    public array $pauseBuffer = [];

    public int $machineId = 2;
}
