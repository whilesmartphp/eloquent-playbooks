<?php

namespace Whilesmart\Playbooks\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Whilesmart\Playbooks\Contracts\SubjectDescriber;
use Whilesmart\Playbooks\Enums\PlaybookEntryStatus;
use Whilesmart\Playbooks\Events\PlaybookEntryConfirmed;
use Whilesmart\Playbooks\Events\PlaybookEntrySaved;
use Whilesmart\Playbooks\Models\PlaybookEntry;

/** Reads and writes one owner's playbook for one subject. */
class Playbook
{
    public function __construct(
        private readonly PlaybookSchema $schema,
        private readonly SubjectDescriber $describer,
    ) {}

    /**
     * @return class-string<PlaybookEntry>
     */
    public function model(): string
    {
        return config('playbooks.models.entry', PlaybookEntry::class);
    }

    public function query(string $ownerType, mixed $ownerId, ?string $subjectType = null, mixed $subjectId = null): Builder
    {
        return $this->model()::query()
            ->forOwner($ownerType, $ownerId)
            ->forSubject($subjectType, $subjectId)
            ->whereIn('type', $this->schema->types());
    }

    public function queryFor(Model $owner, ?Model $subject = null): Builder
    {
        return $this->query($owner->getMorphClass(), $owner->getKey(), $subject?->getMorphClass(), $subject?->getKey());
    }

    /**
     * Confirmed entries filling the type slots, plus the suggestions awaiting review.
     *
     * @return array<string, mixed>
     */
    public function grouped(Model $owner, ?Model $subject = null): array
    {
        $out = $this->group($this->queryFor($owner, $subject)->confirmed()->get());
        $out['suggestions'] = $this->queryFor($owner, $subject)->suggested()->get()->values()->all();

        return $out;
    }

    /** The subject's confirmed playbook as a prompt fragment, or an empty string. */
    public function prompt(Model $owner, ?Model $subject = null): string
    {
        $all = $this->group($this->queryFor($owner, $subject)->confirmed()->get());
        $sections = [];

        foreach ($this->headings() as $type => $heading) {
            $value = $all[$type] ?? null;
            $entries = is_array($value) ? $value : array_filter([$value]);

            if ($entries === []) {
                continue;
            }

            $sections[] = $heading.":\n".implode("\n", array_map(fn (PlaybookEntry $e): string => $this->render($e), $entries));
        }

        if ($sections === []) {
            return '';
        }

        $intro = (string) config('playbooks.prompt_intro', 'Playbook, treat these as the source of truth for who to reach and why:');

        return "\n\n".$intro."\n\n".implode("\n\n", $sections);
    }

    /**
     * One grounding fragment covering several subjects, each block headed by the subject's label.
     *
     * @param  iterable<int, Model>  $subjects
     */
    public function promptForSubjects(Model $owner, iterable $subjects): string
    {
        $blocks = [];

        foreach ($subjects as $subject) {
            $fragment = $this->prompt($owner, $subject);

            if ($fragment !== '') {
                $blocks[] = 'For "'.$this->describer->label($subject).'":'.$fragment;
            }
        }

        return $blocks === [] ? '' : "\n\n".implode("\n\n", $blocks);
    }

    /**
     * A readable summary of an entry: lists become comma-joined, scalars print inline.
     */
    public function render(PlaybookEntry $entry): string
    {
        $title = trim((string) $entry->title);
        $head = $title !== '' ? $title : ucfirst((string) $entry->type);
        $parts = [];

        foreach ((array) $entry->metadata as $key => $value) {
            // Underscore-prefixed keys hold provenance (source, confidence), not content.
            if (str_starts_with((string) $key, '_')) {
                continue;
            }

            $value = is_array($value)
                ? implode(', ', array_filter(array_map('trim', array_map('strval', $value))))
                : trim((string) $value);

            if ($value !== '') {
                $parts[] = '  '.ucfirst(str_replace('_', ' ', (string) $key)).': '.$value;
            }
        }

        return $parts === [] ? "- {$head}" : "- {$head}\n".implode("\n", $parts);
    }

    /** Confirms an entry; for a single type this retires the subject's other confirmed entries. */
    public function confirm(PlaybookEntry $entry): PlaybookEntry
    {
        $entry->status = PlaybookEntryStatus::Confirmed;
        $entry->save();
        $this->retireOtherSingles($entry);

        event(new PlaybookEntryConfirmed($entry));

        return $entry;
    }

    /**
     * Saves a person's entry as confirmed.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveConfirmed(PlaybookEntry $entry, array $attributes): PlaybookEntry
    {
        $created = ! $entry->exists;
        $entry->fill(array_intersect_key($attributes, array_flip(['owner_type', 'owner_id', 'subject_type', 'subject_id', 'type', 'title'])));
        $entry->metadata = $this->schema->filter((string) $entry->type, (array) ($attributes['metadata'] ?? []));
        $entry->body = ($attributes['body'] ?? null) ?: $this->render($entry);
        $entry->status = PlaybookEntryStatus::Confirmed;
        $entry->save();
        $this->retireOtherSingles($entry);

        event(new PlaybookEntrySaved($entry, $created));

        return $entry;
    }

    /**
     * Saves or refreshes a drafted suggestion, matched on type (and title for collection types).
     *
     * @param  array<string, mixed>  $fields
     */
    public function saveSuggestion(
        string $ownerType,
        mixed $ownerId,
        ?string $subjectType,
        mixed $subjectId,
        string $type,
        string $title,
        array $fields,
        ?string $sourceUrl = null,
        ?int $confidence = null,
    ): PlaybookEntry {
        $metadata = $this->schema->filter($type, $fields);

        if ($sourceUrl !== null && $sourceUrl !== '') {
            $metadata['_source_url'] = $sourceUrl;
        }

        if ($confidence !== null) {
            $metadata['_confidence'] = max(0, min($confidence, 100));
        }

        $scope = [
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'type' => $type,
            'status' => PlaybookEntryStatus::Suggested->value,
        ];

        $match = $this->schema->isSingle($type) ? $scope : $scope + ['title' => $title];

        /** @var PlaybookEntry $entry */
        $entry = $this->model()::withTrashed()->firstOrNew($match);
        $created = ! $entry->exists;
        $entry->fill($scope);
        $entry->title = $title;
        $entry->metadata = $metadata;
        $entry->body = $this->render($entry);
        $entry->deleted_at = null;
        $entry->save();

        event(new PlaybookEntrySaved($entry, $created));

        return $entry;
    }

    private function retireOtherSingles(PlaybookEntry $entry): void
    {
        if (! $this->schema->isSingle((string) $entry->type)) {
            return;
        }

        $this->model()::query()
            ->forOwner((string) $entry->owner_type, $entry->owner_id)
            ->forSubject($entry->subject_type, $entry->subject_id)
            ->where('type', $entry->type)
            ->confirmed()
            ->whereKeyNot($entry->getKey())
            ->get()
            ->each->delete();
    }

    /**
     * @param  Collection<int, PlaybookEntry>  $rows
     * @return array<string, mixed>
     */
    private function group(Collection $rows): array
    {
        $byType = $rows->groupBy('type');
        $out = [];

        foreach ($this->schema->single() as $type) {
            $out[$type] = $byType->get($type)?->first();
        }

        foreach ($this->schema->many() as $type) {
            $out[$type] = ($byType->get($type) ?? collect())->values()->all();
        }

        return $out;
    }

    /**
     * The types that ground a prompt, in reading order.
     *
     * @return array<string, string>
     */
    private function headings(): array
    {
        return [
            'icp' => 'Ideal customer profile',
            'persona' => 'Target buyer personas',
            'offer' => 'The offer',
            'positioning' => 'Positioning',
        ];
    }
}
