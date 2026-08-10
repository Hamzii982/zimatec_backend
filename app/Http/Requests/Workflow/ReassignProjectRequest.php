<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class ReassignProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'assignee_id' => 'Zuständiger Benutzer',
        ];
    }
}
