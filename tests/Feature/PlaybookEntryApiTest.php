<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Product;
use Tests\TestCase;
use Whilesmart\Playbooks\Events\PlaybookEntryConfirmed;
use Whilesmart\Playbooks\Events\PlaybookEntryDeleted;
use Whilesmart\Playbooks\Events\PlaybookEntrySaved;
use Whilesmart\Playbooks\Models\PlaybookEntry;

class PlaybookEntryApiTest extends TestCase
{
    #[Test]
    public function it_creates_a_confirmed_entry_keeping_only_the_kind_s_fields(): void
    {
        Event::fake([PlaybookEntrySaved::class]);

        $this->postJson('/api/playbook-entries', $this->entryPayload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.metadata.role', 'Head of Developer Experience')
            ->assertJsonMissingPath('data.metadata.shoe_size')
            ->assertJsonPath('data.body', "- Head of DevEx\n  Role: Head of Developer Experience\n  Pains: tooling sprawl");

        Event::assertDispatched(PlaybookEntrySaved::class, fn (PlaybookEntrySaved $e) => $e->created);
    }

    #[Test]
    public function it_rejects_an_unknown_kind(): void
    {
        $this->postJson('/api/playbook-entries', $this->entryPayload(['kind' => 'horoscope']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kind');

        $this->assertDatabaseCount('playbook_entries', 0);
    }

    #[Test]
    public function a_single_kind_keeps_one_confirmed_entry_per_subject(): void
    {
        $this->postJson('/api/playbook-entries', $this->entryPayload(['kind' => 'icp', 'title' => 'Old ICP', 'metadata' => ['industry' => 'Legacy']]))->assertCreated();
        $this->postJson('/api/playbook-entries', $this->entryPayload(['kind' => 'icp', 'title' => 'New ICP', 'metadata' => ['industry' => 'SaaS']]))->assertCreated();

        $confirmed = PlaybookEntry::where('kind', 'icp')->confirmed()->get();
        $this->assertCount(1, $confirmed);
        $this->assertSame('New ICP', $confirmed->first()->title);
    }

    #[Test]
    public function it_lists_entries_filtered_by_owner_subject_and_kind(): void
    {
        $this->postJson('/api/playbook-entries', $this->entryPayload())->assertCreated();
        $this->postJson('/api/playbook-entries', $this->entryPayload(['kind' => 'competitor', 'title' => 'Rival', 'metadata' => ['name' => 'Rival']]))->assertCreated();
        $this->postJson('/api/playbook-entries', $this->entryPayload(['subject_id' => 2]))->assertCreated();

        $this->getJson('/api/playbook-entries?'.http_build_query([
            'owner_type' => self::OWNER, 'owner_id' => 1,
            'subject_type' => Product::class, 'subject_id' => 1,
            'kind' => 'persona',
        ]))->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.title', 'Head of DevEx');
    }

    #[Test]
    public function it_shows_updates_and_deletes_an_entry(): void
    {
        Event::fake([PlaybookEntryDeleted::class]);
        $id = $this->postJson('/api/playbook-entries', $this->entryPayload())->json('data.id');

        $this->getJson("/api/playbook-entries/{$id}")->assertOk()->assertJsonPath('data.kind', 'persona');

        // A title-only update keeps the existing fields.
        $this->putJson("/api/playbook-entries/{$id}", ['title' => 'VP Engineering'])
            ->assertOk()
            ->assertJsonPath('data.title', 'VP Engineering')
            ->assertJsonPath('data.metadata.role', 'Head of Developer Experience');

        $this->deleteJson("/api/playbook-entries/{$id}")->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertSoftDeleted('playbook_entries', ['id' => $id]);
        Event::assertDispatched(PlaybookEntryDeleted::class);
    }

    #[Test]
    public function accepting_a_suggestion_confirms_it_and_replaces_the_confirmed_single(): void
    {
        Event::fake([PlaybookEntryConfirmed::class]);
        $this->postJson('/api/playbook-entries', $this->entryPayload(['kind' => 'icp', 'title' => 'Old ICP', 'metadata' => ['industry' => 'Legacy']]))->assertCreated();

        $suggestion = PlaybookEntry::create([
            'owner_type' => self::OWNER, 'owner_id' => 1,
            'subject_type' => Product::class, 'subject_id' => 1,
            'kind' => 'icp', 'status' => 'suggested', 'title' => 'New ICP', 'metadata' => ['industry' => 'SaaS'],
        ]);

        $this->postJson("/api/playbook-entries/{$suggestion->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $confirmed = PlaybookEntry::where('kind', 'icp')->confirmed()->get();
        $this->assertCount(1, $confirmed);
        $this->assertSame('New ICP', $confirmed->first()->title);
        Event::assertDispatched(PlaybookEntryConfirmed::class);
    }

    #[Test]
    public function a_failed_create_dispatches_no_event(): void
    {
        Event::fake([PlaybookEntrySaved::class]);

        $this->postJson('/api/playbook-entries', $this->entryPayload(['title' => '']))->assertUnprocessable();

        Event::assertNotDispatched(PlaybookEntrySaved::class);
    }
}
