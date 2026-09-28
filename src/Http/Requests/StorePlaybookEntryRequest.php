<?php

namespace Whilesmart\Playbooks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerRequest;
use Whilesmart\Playbooks\Support\PlaybookSchema;

class StorePlaybookEntryRequest extends FormRequest
{
    use AuthorizesOwnerRequest;

    public function authorize(): bool
    {
        return $this->authorizeOwnerInRequest();
    }

    public function rules(): array
    {
        $schema = app(PlaybookSchema::class);

        return [
            'owner_type' => ['required', 'string'],
            'owner_id' => ['required'],
            'subject_type' => ['nullable', 'string', 'required_with:subject_id'],
            'subject_id' => ['nullable', 'required_with:subject_type'],
            'kind' => ['required', 'string', Rule::in($schema->kinds())],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'metadata.framework' => ['nullable', Rule::in(array_keys($schema->qualificationFrameworks()))],
            'metadata.weights' => ['nullable', 'array'],
            'metadata.weights.*' => ['numeric', 'min:0', 'max:100'],
        ];
    }
}
