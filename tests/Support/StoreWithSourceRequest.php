<?php

namespace Tests\Support;

use Whilesmart\Playbooks\Http\Requests\StorePlaybookEntryRequest;

class StoreWithSourceRequest extends StorePlaybookEntryRequest
{
    public function rules(): array
    {
        return parent::rules() + ['metadata.role' => ['required']];
    }
}
