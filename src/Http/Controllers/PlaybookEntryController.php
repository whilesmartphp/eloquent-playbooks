<?php

namespace Whilesmart\Playbooks\Http\Controllers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Whilesmart\Agents\Facades\Agents;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerController;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;
use Whilesmart\Playbooks\Contracts\ExtractionBudget;
use Whilesmart\Playbooks\Contracts\ExtractionStatusStore;
use Whilesmart\Playbooks\Contracts\ResponseFormatter;
use Whilesmart\Playbooks\Events\PlaybookEntryDeleted;
use Whilesmart\Playbooks\Jobs\ExtractPlaybook;
use Whilesmart\Playbooks\Models\PlaybookEntry;
use Whilesmart\Playbooks\Support\Playbook;

class PlaybookEntryController extends Controller
{
    use AuthorizesOwnerController;

    public function __construct(
        protected Playbook $playbook,
        protected ResponseFormatter $responses,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->scopeAccessibleOwners($this->playbook->model()::query(), $request->user());

        if ($request->filled('owner_type') && $request->filled('owner_id')) {
            $query->forOwner((string) $request->input('owner_type'), $request->input('owner_id'));
        }

        if ($request->filled('subject_type') && $request->filled('subject_id')) {
            $query->forSubject((string) $request->input('subject_type'), $request->input('subject_id'));
        }

        foreach (['kind', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $entries = $query->orderBy('kind')->orderByDesc('updated_at')
            ->paginate((int) $request->input('per_page', 50));

        return $this->responses->success($this->resourceClass()::collection($entries)->response()->getData(true));
    }

    public function store(): JsonResponse
    {
        $request = $this->formRequest('store');
        $model = $this->playbook->model();
        $entry = $this->playbook->saveConfirmed(new $model, $this->attributes($request));

        return $this->responses->success($this->resource($entry), 201);
    }

    public function show(Request $request, PlaybookEntry $playbook_entry): JsonResponse
    {
        $this->authorizeAccessTo($playbook_entry, $request->user());

        return $this->responses->success($this->resource($playbook_entry));
    }

    public function update(PlaybookEntry $playbook_entry): JsonResponse
    {
        $request = $this->formRequest('update');
        $this->authorizeAccessTo($playbook_entry, $request->user());

        // Fields the request leaves out keep their current values.
        $data = $this->attributes($request) + ['title' => $playbook_entry->title, 'metadata' => (array) $playbook_entry->metadata];
        $entry = $this->playbook->saveConfirmed($playbook_entry, $data);

        return $this->responses->success($this->resource($entry->fresh()));
    }

    public function destroy(Request $request, PlaybookEntry $playbook_entry): JsonResponse
    {
        $this->authorizeAccessTo($playbook_entry, $request->user());
        $playbook_entry->delete();

        event(new PlaybookEntryDeleted($playbook_entry));

        return $this->responses->success(['deleted' => true]);
    }

    public function accept(Request $request, PlaybookEntry $playbook_entry): JsonResponse
    {
        $this->authorizeAccessTo($playbook_entry, $request->user());

        return $this->responses->success($this->resource($this->playbook->confirm($playbook_entry)->fresh()));
    }

    public function extract(ExtractionBudget $budget, ExtractionStatusStore $status): JsonResponse
    {
        $request = $this->formRequest('extract');
        $data = $request->validated();

        if (! class_exists(Agents::class)) {
            return $this->responses->failure('Playbook extraction needs whilesmart/eloquent-agents.', 501);
        }

        if (! $budget->allows((string) $data['owner_type'], $data['owner_id'])) {
            return $this->responses->failure('Token limit reached.', 402);
        }

        $status->put($data['owner_type'], $data['owner_id'], $data['subject_type'] ?? null, $data['subject_id'] ?? null, [
            'state' => 'running', 'found' => 0, 'error' => null,
        ]);

        ExtractPlaybook::dispatch(
            (string) $data['owner_type'],
            $data['owner_id'],
            $data['subject_type'] ?? null,
            $data['subject_id'] ?? null,
            array_values($data['own_urls']),
            array_values($data['competitor_urls'] ?? []),
            $request->user()?->getAuthIdentifier(),
        );

        return $this->responses->success(['state' => 'running'], 202);
    }

    public function extractStatus(Request $request, ExtractionStatusStore $status): JsonResponse
    {
        $data = $request->validate([
            'owner_type' => ['required', 'string'],
            'owner_id' => ['required'],
            'subject_type' => ['nullable', 'string'],
            'subject_id' => ['nullable'],
        ]);

        abort_unless(app(OwnerAuthorizer::class)->authorize($request->user(), $data['owner_type'], $data['owner_id']), 403);

        return $this->responses->success(
            $status->get($data['owner_type'], $data['owner_id'], $data['subject_type'] ?? null, $data['subject_id'] ?? null)
        );
    }

    /**
     * Validated input with the full metadata object, which validated() would trim to ruled keys.
     *
     * @return array<string, mixed>
     */
    protected function attributes(FormRequest $request): array
    {
        $attributes = $request->validated();

        if ($request->has('metadata')) {
            $attributes['metadata'] = (array) $request->input('metadata');
        }

        return $attributes;
    }

    /**
     * Resolving a form request through the container runs its authorization and validation.
     */
    protected function formRequest(string $key): FormRequest
    {
        return app(config("playbooks.requests.{$key}"));
    }

    protected function resourceClass(): string
    {
        return config('playbooks.resources.entry');
    }

    protected function resource(PlaybookEntry $entry): mixed
    {
        $class = $this->resourceClass();

        return new $class($entry);
    }
}
