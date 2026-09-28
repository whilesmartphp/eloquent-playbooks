<?php

namespace Whilesmart\Playbooks\Support;

/** The kinds a playbook holds and each kind's fields, read from config. */
class PlaybookSchema
{
    /**
     * @return array<int, string>
     */
    public function single(): array
    {
        return array_values((array) config('playbooks.kinds.single', []));
    }

    /**
     * @return array<int, string>
     */
    public function many(): array
    {
        return array_values((array) config('playbooks.kinds.many', []));
    }

    /**
     * @return array<int, string>
     */
    public function kinds(): array
    {
        return [...$this->single(), ...$this->many()];
    }

    public function isKind(string $kind): bool
    {
        return in_array($kind, $this->kinds(), true);
    }

    public function isSingle(string $kind): bool
    {
        return in_array($kind, $this->single(), true);
    }

    /**
     * @return array<int, string>
     */
    public function fields(string $kind): array
    {
        return array_values((array) config("playbooks.fields.{$kind}", []));
    }

    /**
     * Keep only the fields this kind recognises.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function filter(string $kind, array $metadata): array
    {
        return array_intersect_key($metadata, array_flip($this->fields($kind)));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function qualificationFrameworks(): array
    {
        return (array) config('playbooks.qualification_frameworks', []);
    }
}
