<?php
// app/Services/AI/Tools/PrinterProblemLookupTool.php
namespace App\Services\AI\Tools;

use App\Models\PrinterProblem;
use App\Services\AI\Contracts\AiToolContract;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\Authenticatable;

class PrinterProblemLookupTool implements AiToolContract
{
    public function name(): string
    {
        return 'get_printer_problem';
    }

    public function description(): string
    {
        return 'Fetch the details of a specific printer problem by its problem UID or order number — machine settings, error info, and any existing AI diagnosis on file. Use before diagnosing an issue or drafting a manufacturer email.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'problem_uid' => ['type' => 'string', 'description' => 'Exact problem UID, if known.'],
                'order_number' => ['type' => 'string', 'description' => 'Order number tied to the problem, if problem_uid is not known.'],
            ],
        ];
    }

    public function isAuthorized(?Authenticatable $user): bool
    {
        // return $user !== null;
        return true;
    }

    public function handle(array $arguments): array
    {
        $query = PrinterProblem::query();

        if (!empty($arguments['problem_uid'])) {
            $query->where('problem_uid', $arguments['problem_uid']);
        } elseif (!empty($arguments['order_number'])) {
            $query->where('order_number', $arguments['order_number']);
        } else {
            return ['error' => 'Either problem_uid or order_number is required.'];
        }

        $problem = $query->first();

        if (!$problem) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'problem_uid' => $problem->problem_uid,
            'order_number' => $problem->order_number,
            'designation' => $problem->designation,
            'version_number' => $problem->version_number,
            'design_nozzle_diameter' => $problem->design_nozzle_diameter,
            'tool_nozzle_diameter' => $problem->tool_nozzle_diameter,
            'material' => $problem->material,
            'print_temperature' => $problem->print_temperature,
            'bed_temperature' => $problem->bed_temperature,
            'nozzle_height' => $problem->nozzle_height,
            'offsets' => ['x' => $problem->offset_x, 'y' => $problem->offset_y, 'z' => $problem->offset_z],
            'maintenance_completed' => $problem->maintenance_completed,
            'machine_error_id' => $problem->machine_error_id,
            'short_description' => Str::limit((string) $problem->short_description, 500),
            'operator_explanation' => Str::limit((string) $problem->operator_explanation, 500),
            'status' => $problem->status,
            'existing_issue_type' => $problem->issue_type,
            'existing_ai_troubleshooting' => Str::limit((string) $problem->ai_troubleshooting, 500),
        ];
    }
}