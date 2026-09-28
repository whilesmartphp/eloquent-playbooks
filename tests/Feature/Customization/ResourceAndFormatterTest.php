<?php

namespace Tests\Feature\Customization;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BareFormatter;
use Tests\Support\CompactEntryResource;
use Tests\TestCase;

class ResourceAndFormatterTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('playbooks.resources.entry', CompactEntryResource::class);
        $app['config']->set('playbooks.response_formatter', BareFormatter::class);
    }

    #[Test]
    public function a_configured_resource_and_formatter_shape_the_response(): void
    {
        $this->postJson('/api/playbook-entries', $this->entryPayload())
            ->assertCreated()
            ->assertExactJson(['result' => ['ref' => 'pb-1', 'type' => 'persona']]);
    }
}
