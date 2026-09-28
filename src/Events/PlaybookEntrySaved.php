<?php

namespace Whilesmart\Playbooks\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Whilesmart\Playbooks\Models\PlaybookEntry;

class PlaybookEntrySaved
{
    use Dispatchable, SerializesModels;

    public function __construct(public PlaybookEntry $entry, public bool $created) {}
}
