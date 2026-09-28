<?php

namespace Whilesmart\Playbooks\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaybookEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'type' => $this->type,
            'status' => $this->status?->value,
            'title' => $this->title,
            'body' => $this->body,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
