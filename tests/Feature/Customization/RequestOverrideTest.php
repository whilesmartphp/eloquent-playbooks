<?php

namespace Tests\Feature\Customization;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\StoreWithSourceRequest;
use Tests\TestCase;

class RequestOverrideTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('playbooks.requests.store', StoreWithSourceRequest::class);
    }

    #[Test]
    public function a_configured_store_request_adds_its_own_rules(): void
    {
        $payload = $this->entryPayload(['metadata' => ['pains' => ['x']]]);

        $this->postJson('/api/playbook-entries', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('metadata.role');
    }
}
