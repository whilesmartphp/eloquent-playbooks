<?php

namespace Whilesmart\Playbooks\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Whilesmart\Agents\Facades\Agents;
use Whilesmart\Agents\ValueObjects\ToolContext;
use Whilesmart\Playbooks\Contracts\ExtractionBudget;
use Whilesmart\Playbooks\Contracts\ExtractionStatusStore;
use Whilesmart\Playbooks\Contracts\SubjectDescriber;
use Whilesmart\Playbooks\Contracts\UsageRecorder;
use Whilesmart\Playbooks\Enums\PlaybookEntryStatus;
use Whilesmart\Playbooks\Events\PlaybookExtractionFinished;
use Whilesmart\Playbooks\Support\Playbook;

/** Drafts playbook suggestions from web pages. */
class ExtractPlaybook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * @param  array<int, string>  $ownUrls
     * @param  array<int, string>  $competitorUrls
     * @param  array<string, mixed>  $scope  Extra tool context scope (for example a library owner).
     */
    public function __construct(
        public readonly string $ownerType,
        public readonly int|string $ownerId,
        public readonly ?string $subjectType,
        public readonly int|string|null $subjectId,
        public readonly array $ownUrls,
        public readonly array $competitorUrls = [],
        public readonly int|string|null $userId = null,
        public readonly array $scope = [],
    ) {}

    public function handle(
        Playbook $playbook,
        ExtractionBudget $budget,
        UsageRecorder $usage,
        ExtractionStatusStore $status,
        SubjectDescriber $describer,
    ): void {
        if (! class_exists(Agents::class)) {
            $this->finish($status, 'failed', 0, 'Playbook extraction needs whilesmart/eloquent-agents.');

            return;
        }

        if (! $budget->allows($this->ownerType, $this->ownerId)) {
            $this->finish($status, 'failed', 0, 'Token limit reached.');

            return;
        }

        $subject = $this->subject();

        if ($this->subjectType !== null && $subject === null) {
            $this->finish($status, 'failed', 0, 'Subject not found.');

            return;
        }

        $startedAt = now();
        $harness = (string) config('playbooks.extraction.harness', 'playbook-extractor');

        $context = new ToolContext(
            user: $this->user(),
            scope: [
                'owner_type' => $this->ownerType,
                'owner_id' => $this->ownerId,
                'subject_type' => $this->subjectType,
                'subject_id' => $this->subjectId,
            ] + $this->scope,
        );

        $result = Agents::run($harness, $this->input($subject, $describer), $context);

        $usage->record($this->ownerType, $this->ownerId, $result->usage, [
            'operation' => $harness,
            'provider' => (string) config('agents.provider'),
            'model' => (string) config('agents.model'),
            'ok' => $result->ok,
        ]);

        $found = $playbook->query($this->ownerType, $this->ownerId, $this->subjectType, $this->subjectId)
            ->where('status', PlaybookEntryStatus::Suggested->value)
            ->where('updated_at', '>=', $startedAt)
            ->count();

        if (! $result->ok) {
            Log::warning('Playbook extraction failed', [
                'owner_type' => $this->ownerType,
                'owner_id' => $this->ownerId,
                'error' => $result->error,
            ]);
        }

        $this->finish($status, $result->ok ? 'done' : 'failed', $found, $result->ok ? null : $result->error);
    }

    private function input(?Model $subject, SubjectDescriber $describer): string
    {
        $own = implode("\n", array_map(fn (string $url): string => '- '.$url, $this->ownUrls));
        $competitors = $this->competitorUrls === []
            ? '(none provided)'
            : implode("\n", array_map(fn (string $url): string => '- '.$url, $this->competitorUrls));

        return ($subject !== null ? 'About: '.$describer->describe($subject)."\n\n" : '')
            ."The seller's own pages:\n{$own}\n\n"
            ."Competitor pages:\n{$competitors}\n\n"
            .'Read these and draft the playbook. Save every entry with playbook.save; each is a suggestion a person will review.';
    }

    private function subject(): ?Model
    {
        if ($this->subjectType === null) {
            return null;
        }

        $class = Relation::getMorphedModel($this->subjectType) ?? $this->subjectType;

        return class_exists($class) ? $class::query()->find($this->subjectId) : null;
    }

    private function user(): mixed
    {
        if ($this->userId === null) {
            return null;
        }

        $guard = (string) config('auth.defaults.guard', 'web');
        $provider = config("auth.guards.{$guard}.provider");

        return $provider ? app('auth')->createUserProvider($provider)?->retrieveById($this->userId) : null;
    }

    private function finish(ExtractionStatusStore $status, string $state, int $found, ?string $error): void
    {
        $status->put($this->ownerType, $this->ownerId, $this->subjectType, $this->subjectId, [
            'state' => $state,
            'found' => $found,
            'error' => $error,
        ]);

        event(new PlaybookExtractionFinished($this->ownerType, $this->ownerId, $this->subjectType, $this->subjectId, $state, $found, $error));
    }
}
