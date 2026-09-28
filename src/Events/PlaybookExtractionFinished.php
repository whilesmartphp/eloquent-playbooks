<?php

namespace Whilesmart\Playbooks\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PlaybookExtractionFinished
{
    use Dispatchable;

    public function __construct(
        public string $ownerType,
        public mixed $ownerId,
        public ?string $subjectType,
        public mixed $subjectId,
        public string $state,
        public int $found,
        public ?string $error = null,
    ) {}
}
