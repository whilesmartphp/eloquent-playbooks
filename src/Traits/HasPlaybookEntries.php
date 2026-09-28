<?php

namespace Whilesmart\Playbooks\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * For owners (tenants) that keep playbooks.
 */
trait HasPlaybookEntries
{
    public function playbookEntries(): MorphMany
    {
        return $this->morphMany(config('playbooks.models.entry'), 'owner');
    }
}
