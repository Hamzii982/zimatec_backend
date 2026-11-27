<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFraesdataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,xml', 'max:20480'],
            'position_id' => [
                'nullable',
                'required_without:bauteil_id',
                'integer',
                Rule::exists('positions', 'id')->where('project_id', $projectId),
            ],
            'bauteil_id' => [
                'nullable',
                'required_without:position_id',
                'integer',
                Rule::exists('bauteile', 'id')->where('project_id', $projectId),
            ],
            'related_position_id' => [
                'nullable',
                'integer',
                Rule::exists('positions', 'id')->where('project_id', $projectId),
            ],
            'materials' => ['nullable', 'array', 'max:100'],
            'materials.*.material_id' => ['required', 'integer', 'exists:materials,id'],
            'materials.*.tool_number' => ['nullable', 'string', 'max:64'],
            'materials.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasPosition = $this->filled('position_id');
            $hasBauteil = $this->filled('bauteil_id');
            $hasRelatedPosition = $this->filled('related_position_id');

            if ($hasPosition && $hasBauteil) {
                $validator->errors()->add('bauteil_id', __('fraesdata.validation.one_target'));
            }

            if ($hasRelatedPosition && ! $hasBauteil) {
                $validator->errors()->add('related_position_id', __('fraesdata.validation.related_position_only_for_bauteil'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => __('fraesdata.validation.name_required'),
            'document.required' => __('fraesdata.validation.file_required'),
            'document.mimes' => __('fraesdata.validation.file_invalid'),
            'document.max' => __('fraesdata.validation.file_max'),
            'position_id.required_without' => __('fraesdata.validation.target_required'),
            'bauteil_id.required_without' => __('fraesdata.validation.target_required'),
        ];
    }

    public function attributes(): array
    {
        return [
            'document' => __('fraesdata.fields.document'),
            'name' => __('fraesdata.fields.name'),
            'related_position_id' => __('fraesdata.fields.related_position'),
        ];
    }
}
