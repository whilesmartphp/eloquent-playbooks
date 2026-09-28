<?php

namespace Tests\Feature\Customization;

use InvalidArgumentException;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use Whilesmart\OwnerAccess\OwnerAccessServiceProvider;
use Whilesmart\Playbooks\PlaybooksServiceProvider;

class InvalidConfigTest extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [OwnerAccessServiceProvider::class, PlaybooksServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('playbooks.route_middleware', ['api']);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('playbooks.resources.entry', \stdClass::class);
    }

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (InvalidArgumentException $e) {
            $this->bootError = $e;
        }
    }

    private ?InvalidArgumentException $bootError = null;

    #[Test]
    public function a_configured_class_of_the_wrong_type_fails_with_a_clear_error(): void
    {
        $this->assertNotNull($this->bootError);
        $this->assertStringContainsString('playbooks.resources.entry must be a class that is or extends', $this->bootError->getMessage());
    }
}
