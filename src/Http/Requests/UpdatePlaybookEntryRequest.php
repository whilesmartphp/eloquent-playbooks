<?php

namespace Whilesmart\Playbooks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerRequest;
use Whilesmart\Playbooks\Support\PlaybookSchema;

class UpdatePlaybookEntryRequest extends FormRequest
{
    use AuthorizesOwnerRequest;

    public function authorize(): bool
    {
        return $this->authorizeOwnerOfBoundModel('playbook_entry');
    }

    public function rules(): array
    {
        $schema = app(PlaybookSchema::class);

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'metadata.framework' => ['nullable', Rule::in(array_keys($schema->qualificationFrameworks()))],
            'metadata.weights' => ['nullable', 'array'],
            'metadata.weights.*' => ['numeric', 'min:0', 'max:100'],
        ];
    }
}
