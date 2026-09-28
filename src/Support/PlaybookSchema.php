<?php

namespace Whilesmart\Playbooks\Support;

/** The types a playbook holds and each type's fields, read from config. */
class PlaybookSchema
{
    /**
     * @return array<int, string>
     */
    public function single(): array
    {
        return array_values((array) config('playbooks.types.single', []));
    }

    /**
     * @return array<int, string>
     */
    public function many(): array
    {
        return array_values((array) config('playbooks.types.many', []));
    }

    /**
     * @return array<int, string>
     */
    public function types(): array
    {
        return [...$this->single(), ...$this->many()];
    }

    public function isType(string $type): bool
    {
        return in_array($type, $this->types(), true);
    }

    public function isSingle(string $type): bool
    {
        return in_array($type, $this->single(), true);
    }

    /**
     * @return array<int, string>
     */
    public function fields(string $type): array
    {
        return array_values((array) config("playbooks.fields.{$type}", []));
    }

    /**
     * Keep only the fields this type recognises.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function filter(string $type, array $metadata): array
    {
        return array_intersect_key($metadata, array_flip($this->fields($type)));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function qualificationFrameworks(): array
    {
        return (array) config('playbooks.qualification_frameworks', []);
    }
}
