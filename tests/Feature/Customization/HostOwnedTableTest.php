<?php

namespace Tests\Feature\Customization;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HostOwnedTableTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        // The host owns the table: package migrations stay off and the host's own migration creates it.
        $this->loadMigrationsFrom(__DIR__.'/../../database/host-migrations');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('playbooks.run_migrations', false);
        $app['config']->set('playbooks.playbooks_table', 'sales_playbook_entries');
    }

    #[Test]
    public function entries_live_in_the_host_s_table(): void
    {
        $this->postJson('/api/playbook-entries', $this->entryPayload())->assertCreated();

        $this->assertDatabaseCount('sales_playbook_entries', 1);
        $this->assertFalse(Schema::hasTable('playbook_entries'));
    }
}
