<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;
use Whilesmart\Playbooks\Models\PlaybookEntry;

class PlaybookEntryAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(OwnerAuthorizer::class, new class implements OwnerAuthorizer
        {
            public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
            {
                return false;
            }

            public function scope(Builder $query, ?Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
            {
                return $query->whereRaw('0 = 1');
            }
        });
    }

    #[Test]
    public function store_and_extract_are_forbidden_when_the_authorizer_denies(): void
    {
        Queue::fake();

        $this->postJson('/api/playbook-entries', $this->entryPayload())->assertForbidden();
        $this->postJson('/api/playbook-entries/extract', [
            'owner_type' => self::OWNER, 'owner_id' => 1, 'own_urls' => ['https://acme.example'],
        ])->assertForbidden();
        $this->getJson('/api/playbook-entries/extract/status?owner_type='.urlencode(self::OWNER).'&owner_id=1')->assertForbidden();

        $this->assertDatabaseCount('playbook_entries', 0);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function show_update_destroy_and_accept_are_forbidden_when_the_authorizer_denies(): void
    {
        $entry = PlaybookEntry::create([
            'owner_type' => self::OWNER, 'owner_id' => 1, 'kind' => 'persona', 'status' => 'suggested', 'title' => 'Private',
        ]);

        $this->getJson("/api/playbook-entries/{$entry->id}")->assertForbidden();
        $this->putJson("/api/playbook-entries/{$entry->id}", ['title' => 'Hijacked'])->assertForbidden();
        $this->postJson("/api/playbook-entries/{$entry->id}/accept")->assertForbidden();
        $this->deleteJson("/api/playbook-entries/{$entry->id}")->assertForbidden();

        $this->assertSame('Private', $entry->fresh()->title);
        $this->assertSame('suggested', $entry->fresh()->status->value);
    }

    #[Test]
    public function index_returns_nothing_when_the_scope_denies(): void
    {
        PlaybookEntry::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'kind' => 'persona', 'title' => 'Private']);

        $this->getJson('/api/playbook-entries')->assertOk()->assertJsonPath('data.meta.total', 0);
    }
}
