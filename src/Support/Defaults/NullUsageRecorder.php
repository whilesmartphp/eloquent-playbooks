<?php

namespace Whilesmart\Playbooks\Support\Defaults;

use Whilesmart\Playbooks\Contracts\UsageRecorder;

class NullUsageRecorder implements UsageRecorder
{
    public function record(string $ownerType, mixed $ownerId, array $usage, array $metadata = []): void {}
}
