<?php

namespace Whilesmart\Playbooks\Support\Defaults;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Playbooks\Contracts\SubjectDescriber;

/**
 * Reads `name` (or `title`) and `description` from the subject.
 */
class AttributeSubjectDescriber implements SubjectDescriber
{
    public function label(Model $subject): string
    {
        $name = $subject->getAttribute('name') ?? $subject->getAttribute('title');

        return $name !== null && $name !== '' ? (string) $name : class_basename($subject).' #'.$subject->getKey();
    }

    public function describe(Model $subject): string
    {
        $description = trim((string) $subject->getAttribute('description'));

        return $this->label($subject).($description !== '' ? " ({$description})" : '');
    }
}
