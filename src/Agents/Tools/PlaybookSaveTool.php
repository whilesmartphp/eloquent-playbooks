<?php

namespace Whilesmart\Playbooks\Agents\Tools;

use Whilesmart\Agents\Enums\ToolPermission;
use Whilesmart\Agents\Tools\AbstractTool;
use Whilesmart\Agents\ValueObjects\ParameterSpec;
use Whilesmart\Agents\ValueObjects\ToolContext;
use Whilesmart\Playbooks\Support\Playbook;
use Whilesmart\Playbooks\Support\PlaybookSchema;

/** Saves a drafted entry as a suggestion; owner and subject come from the tool context, never the model. */
class PlaybookSaveTool extends AbstractTool
{
    public function __construct(
        private readonly Playbook $playbook,
        private readonly PlaybookSchema $schema,
    ) {}

    public function name(): string
    {
        return 'playbook.save';
    }

    public function description(): string
    {
        return 'Save one piece of a playbook, drawn from a page you read, as a suggestion for a person to review. '
            .'Call once per entry. Use only what the pages actually say; never invent a fact.';
    }

    public function parameters(): array
    {
        return [
            ParameterSpec::enum('kind', 'What this entry is.', $this->schema->kinds()),
            ParameterSpec::string('title', 'A short name for the entry: the persona role, the competitor name, or a label for the ICP, offer or positioning.'),
            ParameterSpec::string('fields', 'A JSON object of this kind\'s structured fields, keyed by field name, values strings or arrays of strings. Example for a persona: {"role":"VP Engineering","pains":["flaky CI","slow onboarding"]}.'),
            ParameterSpec::string('source_url', 'The page URL this was drawn from; the evidence.'),
            ParameterSpec::number('confidence', 'How strongly the pages support this, 0-100.', false),
        ];
    }

    public function permission(): ToolPermission
    {
        return ToolPermission::WRITE;
    }

    public function authorize(ToolContext $context): bool
    {
        return $context->has('owner_type') && $context->has('owner_id');
    }

    public function handle(array $arguments, ToolContext $context): string|array
    {
        $kind = (string) ($arguments['kind'] ?? '');

        if (! $this->schema->isKind($kind)) {
            return "Unknown playbook kind '{$kind}'.";
        }

        $title = trim((string) ($arguments['title'] ?? ''));

        if ($title === '') {
            return 'An entry needs a title.';
        }

        $decoded = json_decode((string) ($arguments['fields'] ?? '{}'), true);

        $entry = $this->playbook->saveSuggestion(
            (string) $context->get('owner_type'),
            $context->get('owner_id'),
            $context->get('subject_type'),
            $context->get('subject_id'),
            $kind,
            $title,
            is_array($decoded) ? $decoded : [],
            trim((string) ($arguments['source_url'] ?? '')),
            isset($arguments['confidence']) ? (int) $arguments['confidence'] : null,
        );

        return ['saved' => true, 'entry_id' => $entry->getKey(), 'kind' => $kind, 'status' => $entry->status->value];
    }
}
