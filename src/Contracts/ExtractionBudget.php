<?php

namespace Whilesmart\Playbooks\Contracts;

/**
 * Decides whether an owner may start another extraction (a plan's token cap, for example).
 */
interface ExtractionBudget
{
    public function allows(string $ownerType, mixed $ownerId): bool;
}
