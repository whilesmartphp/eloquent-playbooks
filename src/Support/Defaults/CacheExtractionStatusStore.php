<?php

namespace Whilesmart\Playbooks\Support\Defaults;

use Illuminate\Support\Facades\Cache;
use Whilesmart\Playbooks\Contracts\ExtractionStatusStore;

class CacheExtractionStatusStore implements ExtractionStatusStore
{
    public function put(string $ownerType, mixed $ownerId, ?string $subjectType, mixed $subjectId, array $status): void
    {
        Cache::put(
            $this->key($ownerType, $ownerId, $subjectType, $subjectId),
            $status,
            now()->addMinutes((int) config('playbooks.extraction.status_ttl_minutes', 360)),
        );
    }

    public function get(string $ownerType, mixed $ownerId, ?string $subjectType, mixed $subjectId): array
    {
        return Cache::get(
            $this->key($ownerType, $ownerId, $subjectType, $subjectId),
            ['state' => 'idle', 'found' => 0, 'error' => null],
        );
    }

    private function key(string $ownerType, mixed $ownerId, ?string $subjectType, mixed $subjectId): string
    {
        return 'playbooks.extract:'.md5(implode('|', [$ownerType, (string) $ownerId, (string) $subjectType, (string) $subjectId]));
    }
}
