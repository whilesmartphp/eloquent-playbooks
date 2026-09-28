<?php

namespace Tests\Support;

use Illuminate\Http\Resources\Json\JsonResource;

class CompactEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['ref' => 'pb-'.$this->id, 'type' => $this->type];
    }
}
