<?php

namespace Whilesmart\Playbooks\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * For subjects a playbook is about (a product, a brand, a film).
 */
trait HasPlaybook
{
    public function playbook(): MorphMany
    {
        return $this->morphMany(config('playbooks.models.entry'), 'subject');
    }
}
