<?php

namespace App\Http\Controllers;

use App\Models\Fraesdata;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FraesdataController extends Controller
{
    public function projectIndex(Request $request, Project $project)
    {
        $project->load([
            'positions.fraesdata.tools.material.lager',
            'positions.fraesdata.tools.material.shelf',
            'bauteile.fraesdata.tools.material.lager',
            'bauteile.fraesdata.tools.material.shelf',
            'bauteile.fraesdata.relatedPosition',
        ]);
        $latestProjectUpload = $project->fraesdata()->max('created_at');

        $selectedTarget = null;
        $selectedDocuments = collect();
        $targetType = $request->query('target_type');
        $targetId = $request->query('target_id');

        if ($targetType !== null || $targetId !== null) {
            abort_unless(
                in_array($targetType, ['position', 'bauteil'], true)
                    && is_string($targetId)
                    && ctype_digit($targetId),
                404,
            );

            $targets = $targetType === 'position'
                ? $project->positions
                : $project->bauteile;
            $selectedTarget = $targets->firstWhere('id', (int) $targetId);
            abort_unless($selectedTarget, 404);

            $selectedDocuments = $selectedTarget->fraesdata
                ->sortByDesc('created_at')
                ->values();
        }

        return view('user.projects.cnc-data', compact(
            'project',
            'selectedTarget',
            'selectedDocuments',
            'targetType',
            'latestProjectUpload',
        ));
    }

    public function download(Project $project, Fraesdata $fraesdata)
    {
        abort_unless((int) $fraesdata->project_id === (int) $project->id, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($fraesdata->document_path), 404);
        $extension = strtolower(pathinfo($fraesdata->document_path, PATHINFO_EXTENSION));
        $mimeType = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'xml' => 'application/xml',
        ][$extension] ?? 'application/octet-stream';

        return $disk->response(
            $fraesdata->document_path,
            Str::slug($fraesdata->name).'.'.$extension,
            ['Content-Type' => $mimeType],
            'inline',
        );
    }

    public function printDocument(Project $project, Fraesdata $fraesdata)
    {
        abort_unless((int) $fraesdata->project_id === (int) $project->id, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($fraesdata->document_path), 404);

        $extension = strtolower(pathinfo($fraesdata->document_path, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, ['pdf', 'jpg', 'jpeg', 'xml'], true), 404);

        return view('user.projects.cnc-data-print', [
            'project' => $project,
            'document' => $fraesdata,
            'extension' => $extension,
            'fileUrl' => route('projects.cnc-data.download', [$project, $fraesdata]),
        ]);
    }
}
