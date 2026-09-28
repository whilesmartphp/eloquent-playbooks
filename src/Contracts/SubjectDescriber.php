<?php

namespace Whilesmart\Playbooks\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Names a playbook subject in agent input, since the package cannot know the host's models.
 */
interface SubjectDescriber
{
    /**
     * A short name, for headings.
     */
    public function label(Model $subject): string;

    /**
     * A name with any description, for an extractor's input.
     */
    public function describe(Model $subject): string;
}
