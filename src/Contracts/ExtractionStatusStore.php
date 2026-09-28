<?php

namespace Whilesmart\Playbooks\Contracts;

/**
 * Where the state of the latest extraction for one owner and subject is kept.
 */
interface ExtractionStatusStore
{
    /**
     * @param  array{state: string, found: int, error: ?string}  $status
     */
    public function put(string $ownerType, mixed $ownerId, ?string $subjectType, mixed $subjectId, array $status): void;

    /**
     * @return array{state: string, found: int, error: ?string}
     */
    public function get(string $ownerType, mixed $ownerId, ?string $subjectType, mixed $subjectId): array;
}
