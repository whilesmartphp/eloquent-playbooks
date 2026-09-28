<?php

namespace Tests\Feature\Customization;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ReadOnlyMiddleware;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('playbooks.route_groups.extraction', false);
        $app['config']->set('playbooks.write_middleware', [ReadOnlyMiddleware::class]);
        $app['config']->set('playbooks.route_prefix', 'v2');
    }

    #[Test]
    public function a_disabled_group_is_not_routed_and_write_middleware_guards_writes(): void
    {
        $this->assertFalse(Route::has('playbooks.extract'));
        $this->assertFalse(Route::has('playbooks.extract.status'));
        $this->postJson('/v2/playbook-entries', $this->entryPayload())->assertStatus(423);
        $this->getJson('/v2/playbook-entries')->assertOk();
        $this->getJson('/api/playbook-entries')->assertNotFound();
    }
}
