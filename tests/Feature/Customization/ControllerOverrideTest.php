<?php

namespace Tests\Feature\Customization;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CountingController;
use Tests\TestCase;

class ControllerOverrideTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('playbooks.controller', CountingController::class);
    }

    #[Test]
    public function a_configured_controller_serves_the_package_routes(): void
    {
        $id = $this->postJson('/api/playbook-entries', $this->entryPayload())->json('data.id');

        $this->getJson("/api/playbook-entries/{$id}")->assertOk();

        $this->assertSame(1, CountingController::$shows);
    }
}
