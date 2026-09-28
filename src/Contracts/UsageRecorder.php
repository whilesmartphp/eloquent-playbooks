<?php

namespace Whilesmart\Playbooks\Contracts;

/**
 * Records what an extraction cost, for the host's metering.
 */
interface UsageRecorder
{
    /**
     * @param  array{prompt_tokens?: int, completion_tokens?: int}  $usage
     * @param  array<string, mixed>  $metadata
     */
    public function record(string $ownerType, mixed $ownerId, array $usage, array $metadata = []): void;
}
