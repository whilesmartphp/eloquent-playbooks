<?php

namespace Whilesmart\Playbooks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerRequest;

class ExtractPlaybookRequest extends FormRequest
{
    use AuthorizesOwnerRequest;

    public function authorize(): bool
    {
        return $this->authorizeOwnerInRequest();
    }

    public function rules(): array
    {
        return [
            'owner_type' => ['required', 'string'],
            'owner_id' => ['required'],
            'subject_type' => ['nullable', 'string', 'required_with:subject_id'],
            'subject_id' => ['nullable', 'required_with:subject_type'],
            'own_urls' => ['required', 'array', 'min:1'],
            'own_urls.*' => ['required', 'url'],
            'competitor_urls' => ['nullable', 'array'],
            'competitor_urls.*' => ['url'],
        ];
    }
}
