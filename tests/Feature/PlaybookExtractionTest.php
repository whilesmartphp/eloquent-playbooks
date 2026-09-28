<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakePlaybookEngine;
use Tests\Support\Product;
use Tests\TestCase;
use Whilesmart\Agents\Contracts\AgentEngine;
use Whilesmart\Agents\ValueObjects\ToolContext;
use Whilesmart\Playbooks\Agents\Tools\PlaybookSaveTool;
use Whilesmart\Playbooks\Contracts\ExtractionBudget;
use Whilesmart\Playbooks\Contracts\UsageRecorder;
use Whilesmart\Playbooks\Events\PlaybookExtractionFinished;
use Whilesmart\Playbooks\Jobs\ExtractPlaybook;
use Whilesmart\Playbooks\Models\PlaybookEntry;
use Whilesmart\Playbooks\Support\Playbook;

class PlaybookExtractionTest extends TestCase
{
    private function extractPayload(array $overrides = []): array
    {
        return $overrides + [
            'owner_type' => self::OWNER,
            'owner_id' => 1,
            'subject_type' => Product::class,
            'subject_id' => 1,
            'own_urls' => ['https://acme.example'],
            'competitor_urls' => ['https://rival.example'],
        ];
    }

    #[Test]
    public function the_extract_endpoint_queues_a_run_and_reports_it_running(): void
    {
        Queue::fake();

        $this->postJson('/api/playbook-entries/extract', $this->extractPayload())
            ->assertStatus(202)
            ->assertJsonPath('data.state', 'running');

        Queue::assertPushed(ExtractPlaybook::class, fn (ExtractPlaybook $job) => $job->subjectId === 1 && $job->ownUrls === ['https://acme.example']);

        $this->getJson('/api/playbook-entries/extract/status?'.http_build_query([
            'owner_type' => self::OWNER, 'owner_id' => 1, 'subject_type' => Product::class, 'subject_id' => 1,
        ]))->assertOk()->assertJsonPath('data.state', 'running');
    }

    #[Test]
    public function extraction_needs_at_least_one_own_url(): void
    {
        $this->postJson('/api/playbook-entries/extract', $this->extractPayload(['own_urls' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('own_urls');
    }

    #[Test]
    public function extraction_is_refused_when_the_budget_is_spent(): void
    {
        Queue::fake();
        $this->app->instance(ExtractionBudget::class, new class implements ExtractionBudget
        {
            public function allows(string $ownerType, mixed $ownerId): bool
            {
                return false;
            }
        });

        $this->postJson('/api/playbook-entries/extract', $this->extractPayload())->assertStatus(402);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function a_run_saves_suggestions_that_do_not_ground_until_accepted(): void
    {
        Event::fake([PlaybookExtractionFinished::class]);
        $engine = new FakePlaybookEngine;
        $this->app->instance(AgentEngine::class, $engine);
        $recorded = new class implements UsageRecorder
        {
            public array $calls = [];

            public function record(string $ownerType, mixed $ownerId, array $usage, array $metadata = []): void
            {
                $this->calls[] = compact('ownerType', 'ownerId', 'usage', 'metadata');
            }
        };
        $this->app->instance(UsageRecorder::class, $recorded);
        $product = Product::create(['name' => 'SourceAnt', 'description' => 'Software intelligence']);

        dispatch_sync(new ExtractPlaybook(self::OWNER, 1, Product::class, $product->id, ['https://acme.example']));

        $this->assertDatabaseHas('playbook_entries', ['subject_id' => $product->id, 'type' => 'icp', 'status' => 'suggested']);
        $persona = PlaybookEntry::where('type', 'persona')->first();
        $this->assertSame(100, $persona->metadata['_confidence']);
        $this->assertStringContainsString('About: SourceAnt (Software intelligence)', $engine->request->input);
        $this->assertSame(['prompt_tokens' => 20, 'completion_tokens' => 10], $recorded->calls[0]['usage']);

        $playbook = $this->app->make(Playbook::class);
        $owner = new class extends Model
        {
            protected $attributes = ['id' => 1];

            public function getMorphClass(): string
            {
                return 'App\\Models\\Workspace';
            }
        };
        $this->assertSame('', $playbook->prompt($owner, $product));

        $this->getJson('/api/playbook-entries/extract/status?'.http_build_query([
            'owner_type' => self::OWNER, 'owner_id' => 1, 'subject_type' => Product::class, 'subject_id' => $product->id,
        ]))->assertOk()->assertJsonPath('data.state', 'done')->assertJsonPath('data.found', 2);

        Event::assertDispatched(PlaybookExtractionFinished::class, fn ($e) => $e->state === 'done' && $e->found === 2);

        $this->postJson("/api/playbook-entries/{$persona->id}/accept")->assertOk();
        $this->assertStringContainsString('Head of Developer Experience', $playbook->prompt($owner, $product));
    }

    #[Test]
    public function a_run_for_a_missing_subject_fails_without_calling_the_model(): void
    {
        $engine = new FakePlaybookEngine;
        $this->app->instance(AgentEngine::class, $engine);

        dispatch_sync(new ExtractPlaybook(self::OWNER, 1, Product::class, 999, ['https://acme.example']));

        $this->assertNull($engine->request);
        $this->getJson('/api/playbook-entries/extract/status?'.http_build_query([
            'owner_type' => self::OWNER, 'owner_id' => 1, 'subject_type' => Product::class, 'subject_id' => 999,
        ]))->assertJsonPath('data.state', 'failed')->assertJsonPath('data.error', 'Subject not found.');
    }

    #[Test]
    public function the_save_tool_refreshes_a_suggestion_instead_of_duplicating(): void
    {
        $context = new ToolContext(scope: [
            'owner_type' => self::OWNER, 'owner_id' => 1, 'subject_type' => Product::class, 'subject_id' => 1,
        ]);
        $tool = $this->app->make(PlaybookSaveTool::class);
        $args = ['type' => 'icp', 'title' => 'Mid-market', 'fields' => json_encode(['industry' => 'SaaS', 'colour' => 'red']), 'source_url' => 'https://acme.example'];

        $tool->handle($args, $context);
        $tool->handle($args, $context);

        $this->assertSame(1, PlaybookEntry::where('type', 'icp')->count());
        $this->assertSame(['industry' => 'SaaS', '_source_url' => 'https://acme.example'], PlaybookEntry::first()->metadata);
        $this->assertFalse($tool->authorize(new ToolContext(scope: ['owner_type' => self::OWNER])));
        $this->assertSame("Unknown playbook type 'horoscope'.", $tool->handle(['type' => 'horoscope', 'title' => 'x'], $context));
    }
}
