<?php

namespace Whilesmart\Playbooks\Support\Defaults;

use Whilesmart\Playbooks\Contracts\ExtractionBudget;

class UnlimitedExtractionBudget implements ExtractionBudget
{
    public function allows(string $ownerType, mixed $ownerId): bool
    {
        return true;
    }
}
