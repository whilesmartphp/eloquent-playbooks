<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Whilesmart\Agents\AgentsServiceProvider;
use Whilesmart\OwnerAccess\OwnerAccessServiceProvider;
use Whilesmart\Playbooks\PlaybooksServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected const OWNER = 'App\\Models\\Workspace';

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            OwnerAccessServiceProvider::class,
            AgentsServiceProvider::class,
            PlaybooksServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('playbooks.route_middleware', ['api']);
        $app['config']->set('cache.default', 'array');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function entryPayload(array $overrides = []): array
    {
        return $overrides + [
            'owner_type' => self::OWNER,
            'owner_id' => 1,
            'subject_type' => Support\Product::class,
            'subject_id' => 1,
            'type' => 'persona',
            'title' => 'Head of DevEx',
            'metadata' => ['role' => 'Head of Developer Experience', 'pains' => ['tooling sprawl'], 'shoe_size' => 44],
        ];
    }
}
